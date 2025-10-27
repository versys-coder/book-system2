#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
export_to_xls.py
Экспорт заявок из ClickHouse в Excel (data.xlsx) с подробным логированием.

Особенности:
 - ищет config.json в /opt/aquastart и в каталоге скрипта
 - подключается к ClickHouse через clickhouse_connect
 - получает таблицу aquastart, приводит timezone-aware datetime к "naive" (Asia/Yekaterinburg)
 - сохраняет в Excel через openpyxl (временный файл -> атомарная замена)
 - подробное логирование всех этапов в export_to_xls.log
 - уведомляет в Telegram при ошибках (если настроен)
"""

import os
import sys
import json
import time
import socket
import tempfile
import traceback
import logging
from typing import Optional

import pandas as pd
import requests
import clickhouse_connect

# Конфиг: сначала /opt, затем локально
CONFIG_PATHS = [
    '/opt/aquastart/config.json',
    os.path.join(os.path.dirname(__file__), 'config.json')
]

# Глобальный таймаут для новых сокетов (чтобы не "виснуть")
GLOBAL_SOCKET_TIMEOUT = 10
socket.setdefaulttimeout(GLOBAL_SOCKET_TIMEOUT)

# Целевая тай зона для вывода дат в Excel
TARGET_TZ = 'Asia/Yekaterinburg'

# Лог-файл (рядом со скриптом)
LOG_PATH = os.path.join(os.path.dirname(__file__), 'export_to_xls.log')


def setup_logging() -> logging.Logger:
    logger = logging.getLogger('export_to_xls')
    logger.setLevel(logging.DEBUG)
    fmt = logging.Formatter('%(asctime)s %(levelname)s %(message)s', "%Y-%m-%d %H:%M:%S")
    fh = logging.FileHandler(LOG_PATH, encoding='utf-8')
    fh.setFormatter(fmt)
    fh.setLevel(logging.DEBUG)
    logger.handlers = []
    logger.addHandler(fh)
    sh = logging.StreamHandler(sys.stderr)
    sh.setFormatter(fmt)
    sh.setLevel(logging.INFO)
    logger.addHandler(sh)
    return logger


logger = setup_logging()


def log_step(msg: str, level="info"):
    getattr(logger, level, logger.info)(msg)


def load_config() -> dict:
    config = None
    for path in CONFIG_PATHS:
        if os.path.exists(path):
            log_step(f"Using config: {path}")
            with open(path, encoding='utf-8') as f:
                config = json.load(f)
            break
    if config is None:
        msg = "Не найден config.json ни в /opt/aquastart, ни в текущей директории."
        log_step(msg, level="error")
        print(msg, file=sys.stderr)
        sys.exit(1)
    return config


def tg_notify(text: str, cfg: dict) -> None:
    try:
        tg = cfg.get('telegram')
        if not tg or not tg.get('token') or not tg.get('chat_id'):
            log_step("tg_notify: Telegram config is missing", level="warning")
            return
        url = f"https://api.telegram.org/bot{tg['token']}/sendMessage"
        data = {
            "chat_id": tg['chat_id'],
            "text": text,
            "parse_mode": "HTML"
        }
        resp = requests.post(url, data=data, timeout=5)
        if resp.status_code != 200:
            log_step(f"tg_notify: HTTP {resp.status_code}, resp={resp.text}", level="warning")
    except Exception as e:
        log_step(f"tg_notify: {e}", level="error")


def make_clickhouse_client(ch_conf: dict):
    params = {
        'host': ch_conf.get('host'),
        'port': int(ch_conf.get('port', 8443)),
        'username': ch_conf.get('user') or ch_conf.get('username'),
        'password': ch_conf.get('password'),
        'database': ch_conf.get('database'),
        'secure': bool(ch_conf.get('https', True)),
    }
    log_step(f"ClickHouse client params (masked): "
             f"{ {k: ('***' if k=='password' and v else v) for k,v in params.items()} }")
    params = {k: v for k, v in params.items() if v is not None}
    client = clickhouse_connect.get_client(**params)
    return client


def df_from_clickhouse(client, query: str) -> pd.DataFrame:
    log_step("Выполняем ClickHouse запрос...")
    # Попробуем удобный метод query_df
    try:
        if hasattr(client, 'query_df'):
            df = client.query_df(query)
            log_step(f"ClickHouse: query_df вернул {len(df)} строк")
            return df
    except Exception as e:
        log_step(f"[warn] client.query_df failed: {e}", level="warning")

    # Fallback
    result = client.query(query)
    if hasattr(result, 'result_rows') and hasattr(result, 'column_names'):
        rows = result.result_rows
        cols = result.column_names
        log_step(f"ClickHouse: result_rows вернул {len(rows)} строк")
        return pd.DataFrame(rows, columns=cols)
    if hasattr(result, 'named_result'):
        nr = result.named_result()
        log_step(f"ClickHouse: named_result вернул {len(nr)} строк")
        return pd.DataFrame(nr)
    try:
        df = pd.DataFrame(result)
        log_step(f"ClickHouse: DataFrame(result) вернул {len(df)} строк")
        return df
    except Exception as e:
        log_step("Не удалось преобразовать результат ClickHouse в DataFrame: " + str(e), level="error")
        raise


def is_tzaware_dtype(dtype) -> bool:
    try:
        import pandas as pd
        if hasattr(pd, 'DatetimeTZDtype') and isinstance(dtype, pd.DatetimeTZDtype):
            return True
    except Exception:
        pass
    try:
        import pandas as pd
        return pd.api.types.is_datetime64tz_dtype(dtype)
    except Exception:
        return False


def make_datetimes_naive(df: pd.DataFrame, tz: str = TARGET_TZ) -> pd.DataFrame:
    if df is None or df.shape[0] == 0:
        log_step("DataFrame пустой или None, пропускаем обработку дат")
        return df

    for col in list(df.columns):
        try:
            col_dtype = df[col].dtype
            if is_tzaware_dtype(col_dtype):
                log_step(f"Колонка {col}: timezone-aware, приводим к {tz} и делаем naive")
                df[col] = pd.to_datetime(df[col], errors='coerce').dt.tz_convert(tz).dt.tz_localize(None)
                continue
            if pd.api.types.is_datetime64_any_dtype(col_dtype):
                log_step(f"Колонка {col}: datetime64, оставляем как есть")
                continue
            if col.lower() in ('created_at', 'created', 'date', 'timestamp') and df[col].dtype == object:
                parsed = pd.to_datetime(df[col], errors='coerce', utc=True)
                if parsed.notna().sum() > 0:
                    log_step(f"Колонка {col}: строки, парсим в datetime, затем к {tz} и naive")
                    parsed = parsed.dt.tz_convert(tz).dt.tz_localize(None)
                    df[col] = parsed
        except Exception as e:
            log_step(f"make_datetimes_naive: {col} -> {e}", level="warning")
            pass

    # Финальная проверка — если остались tz-aware, убираем их
    for col in list(df.columns):
        try:
            if is_tzaware_dtype(df[col].dtype):
                log_step(f"Финальная обработка: {col} tz-aware, делаем naive")
                df[col] = pd.to_datetime(df[col], errors='coerce').dt.tz_convert(tz).dt.tz_localize(None)
        except Exception as e:
            log_step(f"make_datetimes_naive (final): {col} -> {e}", level="warning")
            pass

    return df


def save_to_excel_atomic(df: pd.DataFrame, path: str, engine: str = 'openpyxl') -> None:
    dirpath = os.path.dirname(path) or '.'
    os.makedirs(dirpath, exist_ok=True)
    fd, tmpname = tempfile.mkstemp(prefix='.tmp_export_', dir=dirpath, suffix='.xlsx')
    os.close(fd)
    try:
        log_step(f"Сохраняем Excel во временный файл: {tmpname}")
        df.to_excel(tmpname, index=False, engine=engine)
        log_step("Замещаем временный файл целевым Excel файлом")
        os.replace(tmpname, path)
        log_step(f"Файл успешно записан: {path}")
    except Exception as e:
        log_step(f"Ошибка при сохранении Excel: {e}", level="error")
        try:
            if os.path.exists(tmpname):
                os.remove(tmpname)
        except Exception:
            pass
        raise


def main():
    log_step("===== Старт export_to_xls.py =====")
    config = load_config()

    if 'clickhouse' not in config:
        msg = "В config.json отсутствует секция 'clickhouse'."
        log_step(msg, level="error")
        tg_notify(msg, config)
        sys.exit(1)

    ch_conf = config['clickhouse']
    excel_path = config.get('excel_path', '/mnt/volchkov/data.xlsx')

    query = """
    SELECT created_at, name, phone, email, birth, parent, wishes, ip
    FROM aquastart
    ORDER BY created_at
    """

    try:
        client = make_clickhouse_client(ch_conf)
    except Exception as e:
        err = traceback.format_exc()
        msg = f"Не удалось создать клиент ClickHouse:\n{e}\n\n{err}"
        log_step(msg, level="error")
        tg_notify(msg, config)
        sys.exit(2)

    try:
        df = df_from_clickhouse(client, query)
        if df is None or len(df) == 0:
            log_step("ClickHouse DataFrame пустой, сохраняем только заголовки.")
            df = pd.DataFrame(columns=['created_at', 'name', 'phone', 'email', 'birth', 'parent', 'wishes', 'ip'])
        else:
            log_step(f"ClickHouse DataFrame: {len(df)} строк, {len(df.columns)} колонок")
        # Приводим datetime-поля к naive в TARGET_TZ
        df = make_datetimes_naive(df, tz=TARGET_TZ)
        log_step(f"DataFrame types:\n{df.dtypes}")
        log_step(f"Первые строки:\n{df.head(5)}")
    except Exception as e:
        err = traceback.format_exc()
        msg = f"Ошибка ClickHouse:\n{e}\n\n{err}"
        log_step(msg, level="error")
        tg_notify(msg, config)
        sys.exit(3)

    # Превращаем все datetime в строки для совместимости с Excel
    try:
        for col in df.columns:
            try:
                if pd.api.types.is_datetime64_any_dtype(df[col].dtype):
                    log_step(f"Столбец {col} приводится к строке для Excel")
                    df[col] = pd.to_datetime(df[col], errors='coerce').dt.strftime('%Y-%m-%d %H:%M:%S')
                    df[col] = df[col].fillna('')
            except Exception as e:
                log_step(f"Преобразование {col} к строке: {e}", level="warning")
    except Exception as e:
        log_step(f"Ошибка при обработке datetime колонок: {e}", level="warning")

    # Сохраняем в Excel (атомарно), пробуем с повторами
    max_retries = 3
    for attempt in range(1, max_retries + 1):
        try:
            save_to_excel_atomic(df, excel_path, engine='openpyxl')
            log_step(f"Экспорт завершён: {excel_path}")
            break
        except Exception as e:
            tb = traceback.format_exc()
            msg = f"Ошибка сохранения Excel (попытка {attempt}/{max_retries}):\n{e}\n\n{tb}"
            log_step(msg, level="error")
            if attempt < max_retries:
                time.sleep(5)
            else:
                tg_notify(msg, config)
                log_step("Не удалось сохранить файл после всех попыток.", level="error")
                sys.exit(4)

    try:
        tg_notify(f"✅ Экспорт выполнен: {excel_path}", config)
    except Exception as e:
        log_step(f"tg_notify (success): {e}", level="warning")

    log_step("===== Завершение export_to_xls.py =====")
    sys.exit(0)


if __name__ == '__main__':
    main()
import React, { useEffect, useMemo, useRef, useState } from "react";
import {
  fetchPoolWorkload,
  type PoolWorkloadResponse,
  type PoolWorkloadSlot,
} from "@app1/api-client/poolWorkload";

// Константы из варианта 2
const BAR_WIDTH = 50;
const BAR_GAP = 5;
const LEFT_PADDING = 30;
const TOP_PADDING = 24;
const TOTAL_LANES = 10;
const LANE_CAPACITY = 12;
const SEGMENT_HEIGHT = 11;
const SEGMENT_GAP = 3;
const COLOR_TOP = "#185a90";
const COLOR_BOTTOM = "#99e8fa";
const INACTIVE_COLOR = "#eaf1f8";
const HOUR_START = 7;
const HOUR_END = 21;

type PopupState =
  | {
      left: number;
      top: number;
      value: number;
      hour: number;
      isBreak: boolean;
      visible: boolean;
      date: string;
      freePlaces: number;
      freeLanes: number;
    }
  | null;

// Линейная интерполяция цвета (как в исходном коде варианта 2)
function lerpColor(a: string, b: string, t: number) {
  const ah = a.replace("#", "");
  const bh = b.replace("#", "");
  const ar = parseInt(ah.slice(0, 2), 16);
  const ag = parseInt(ah.slice(2, 4), 16);
  const ab = parseInt(ah.slice(4, 6), 16);
  const br = parseInt(bh.slice(0, 2), 16);
  const bg = parseInt(bh.slice(2, 4), 16);
  const bb = parseInt(bh.slice(4, 6), 16);
  const rr = Math.round(ar + (br - ar) * t);
  const rg = Math.round(ag + (bg - ag) * t);
  const rb = Math.round(ab + (bb - ab) * t);
  return (
    "#" +
    ((1 << 24) + (rr << 16) + (rg << 8) + rb).toString(16).slice(1)
  );
}

function formatDateShortRu(iso: string) {
  if (!iso) return "";
  const [, m, d] = iso.split("-");
  const months = [
    "янв",
    "фев",
    "мар",
    "апр",
    "май",
    "июн",
    "июл",
    "авг",
    "сен",
    "окт",
    "ноя",
    "дек",
  ];
  return `${+d} ${months[+m - 1]}`;
}

function isBreakHour(dateIso: string, hour: number) {
  if (hour !== 12) return false;
  const d = new Date(dateIso).getDay();
  return d >= 1 && d <= 5;
}

export default function Graph2() {
  const [slots, setSlots] = useState<PoolWorkloadSlot[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [popup, setPopup] = useState<PopupState>(null);

  // Текущий час (локальное время)
  const nowHour = new Date().getHours();

  // refs
  const containerRef = useRef<HTMLDivElement | null>(null);
  const scrollOuterRef = useRef<HTMLDivElement | null>(null);
  const popupTimeout = useRef<number | null>(null);

  const hours = useMemo(
    () =>
      Array.from({ length: HOUR_END - HOUR_START + 1 }, (_, i) => HOUR_START + i),
    []
  );

  const dates = useMemo(() => {
    const uniq = Array.from(new Set(slots.map((s) => s.date)));
    uniq.sort();
    return uniq;
  }, [slots]);

  const byKey = useMemo(() => {
    const m = new Map<string, PoolWorkloadSlot>();
    for (const s of slots) m.set(`${s.date}_${s.hour}`, s);
    return m;
  }, [slots]);

  // Загрузка данных
  useEffect(() => {
    let mounted = true;
    (async () => {
      try {
        setLoading(true);
        setError(null);
        const data: PoolWorkloadResponse = await fetchPoolWorkload({
          start_hour: HOUR_START,
          end_hour: HOUR_END,
        });
        if (!mounted) return;
        setSlots(data.slots || []);
      } catch (e: any) {
        setError(e?.message || "Ошибка загрузки");
      } finally {
        setLoading(false);
      }
    })();
    return () => {
      mounted = false;
    };
  }, []);

  // Автоскролл к текущему часу
  useEffect(() => {
    if (!scrollOuterRef.current) return;
    const idx = hours.indexOf(nowHour);
    if (idx < 0) return;
    // Пытаемся найти заголовок часа
    const target = scrollOuterRef.current.querySelector(
      `.graph2-hour[data-hour='${nowHour}']`
    );
    if (target) {
      (target as HTMLElement).scrollIntoView({
        behavior: "smooth",
        inline: "center",
        block: "nearest",
      });
    }
  }, [slots, nowHour, hours]);

  const showPopup = (
    ev: React.MouseEvent,
    freeLanes: number,
    hour: number,
    isBreak: boolean,
    date: string,
    freePlaces: number
  ) => {
    const tgt = ev.currentTarget as HTMLElement;
    const rect = tgt.getBoundingClientRect();
    setPopup({
      left: rect.left + rect.width / 2,
      top: rect.top - 8,
      value: freeLanes,
      hour,
      isBreak,
      visible: true,
      date,
      freePlaces,
      freeLanes,
    });
  };

  const hidePopup = () => {
    if (popupTimeout.current) window.clearTimeout(popupTimeout.current);
    popupTimeout.current = window.setTimeout(
      () =>
        setPopup((p) => (p ? { ...p, visible: false } : null)),
      80
    );
  };

  const handleBookPlaceholder = () => {
    if (!popup || popup.isBreak || popup.freeLanes <= 0) return;
    // Заглушка — просто лог
    console.log("BOOK PLACEHOLDER:", {
      date: popup.date,
      hour: popup.hour,
      freeLanes: popup.freeLanes,
      freePlaces: popup.freePlaces,
    });
    // Можно в будущем пробросить onBook в компонент и вызывать реальную логику
  };

  return (
    <div className="graph2-wrap" ref={containerRef}>
      <h1 className="graph2-title">
        Загруженность — график по свободным дорожкам
      </h1>

      <div className="graph2-grid-scroll" ref={scrollOuterRef}>
        <div
          className="graph2-grid"
          style={{ paddingLeft: LEFT_PADDING, paddingTop: TOP_PADDING }}
        >
          {/* Шапка часов */}
            <div className="graph2-hours">
              {hours.map((h) => (
                <div
                  key={h}
                  data-hour={h}
                  className={
                    "graph2-hour" + (h === nowHour ? " current-hour" : "")
                  }
                >
                  {String(h).padStart(2, "0")}:00
                </div>
              ))}
            </div>

          {/* Строки по датам */}
          <div className="graph2-rows">
            {dates.map((dateIso) => (
              <div className="graph2-row" key={dateIso}>
                <div className="graph2-date">{formatDateShortRu(dateIso)}</div>
                <div className="graph2-bars">
                  {hours.map((h) => {
                    const s = byKey.get(`${dateIso}_${h}`);
                    const isBreak = isBreakHour(dateIso, h) || !!s?.isBreak;
                    const freeLanes = isBreak
                      ? 0
                      : Math.max(0, Math.min(TOTAL_LANES, s?.freeLanes ?? 0));
                    const freePlaces = isBreak
                      ? 0
                      : s?.freePlaces ?? freeLanes * LANE_CAPACITY;

                    // Генерация сегментов (сверху вниз)
                    const segments = Array.from(
                      { length: TOTAL_LANES },
                      (_, i) => {
                        const idxFromTop = i;
                        const isFree = idxFromTop < freeLanes;
                        const t =
                          freeLanes <= 1
                            ? 1
                            : (freeLanes - idxFromTop) / freeLanes;
                        const color = isFree
                          ? lerpColor(COLOR_BOTTOM, COLOR_TOP, t)
                          : INACTIVE_COLOR;
                        return (
                          <div
                            key={i}
                            className="graph2-seg"
                            style={{
                              background: isBreak
                                ? INACTIVE_COLOR
                                : color,
                              height: SEGMENT_HEIGHT,
                              marginBottom: SEGMENT_GAP,
                            }}
                          />
                        );
                      }
                    );

                    const barClasses =
                      "graph2-bar" +
                      (isBreak ? " is-break" : "") +
                      (h === nowHour ? " current-hour-bar" : "");

                    return (
                      <div
                        key={`${dateIso}_${h}`}
                        className={barClasses}
                        style={{ width: BAR_WIDTH, marginRight: BAR_GAP }}
                        data-hour={h}
                        onMouseEnter={(e) =>
                          showPopup(
                            e,
                            freeLanes,
                            h,
                            isBreak,
                            dateIso,
                            freePlaces
                          )
                        }
                        onMouseLeave={hidePopup}
                        title={
                          isBreak
                            ? "ПЕРЕРЫВ"
                            : `${freeLanes} дорожек • ${freePlaces} мест`
                        }
                      >
                        <div className="graph2-bar-inner">{segments}</div>
                      </div>
                    );
                  })}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Попап */}
      {popup && (
        <div
          className="poolworkload-popup-wrapper"
          style={{
            left: popup.left,
            top: popup.top,
            opacity: popup.visible ? 1 : 0,
          }}
        >
          {!popup.isBreak ? (
            <div className="poolworkload-popup">
              <div className="poolworkload-popup-hour">
                {popup.hour}:00 — {popup.hour + 1}:00
              </div>
              <div className="poolworkload-popup-value">
                {popup.value}
                <span className="poolworkload-popup-unit">дорожек</span>
              </div>
              <div className="poolworkload-popup-desc">
                {popup.freePlaces} мест
              </div>
              <button
                className="graph2-book-btn"
                disabled={popup.isBreak || popup.freeLanes <= 0}
                onClick={handleBookPlaceholder}
              >
                Забронировать
              </button>
            </div>
          ) : (
            <div className="poolworkload-popup-break">ПЕРЕРЫВ</div>
          )}
          <div className="poolworkload-popup-arrow">
            <svg width={18} height={12} viewBox="0 0 18 12" fill="none">
              <path
                d="M0 0 Q9 16 18 0"
                fill="#fff"
                stroke="#eaf1f8"
                strokeWidth="1.5"
              />
            </svg>
          </div>
        </div>
      )}

      {loading && <div className="graph2-loading">Загрузка данных…</div>}
      {error && <div className="graph2-error">Ошибка: {error}</div>}
    </div>
  );
}
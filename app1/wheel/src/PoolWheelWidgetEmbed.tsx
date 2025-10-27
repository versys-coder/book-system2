import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  fetchPoolWorkload,
  PoolWorkloadSlot,
  PoolWorkloadResponse,
} from "../../src/api/poolWorkload";

/* ---------- Константы ---------- */
const HOUR_START = 7;
const HOUR_END = 21;
const allHours: number[] = Array.from({ length: HOUR_END - HOUR_START + 1 }, (_, i) => HOUR_START + i);

const WEEKDAYS_RU = ["ВОСКРЕСЕНЬЕ","ПОНЕДЕЛЬНИК","ВТОРНИК","СРЕДА","ЧЕТВЕРГ","ПЯТНИЦА","СУББОТА"];
const MONTHS_RU = [
  "ЯНВАРЯ","ФЕВРАЛЯ","МАРТА","АПРЕЛЯ","МАЯ","ИЮНЯ",
  "ИЮЛЯ","АВГУСТА","СЕНТЯБРЯ","ОКТЯБРЯ","НОЯБРЯ","ДЕКАБРЯ"
];

function pad2(n: number) { return n < 10 ? "0"+n : String(n); }

function formatHeaderDate(iso: string) {
  if (!iso) return "";
  const d = new Date(iso);
  const dow = WEEKDAYS_RU[d.getDay()];
  const day = pad2(d.getDate());
  const month = MONTHS_RU[d.getMonth()];
  return `${dow}, ${day} ${month}`;
}

// Для колеса дат: 01.09
function formatIsoToDDMM(iso: string) {
  if (!iso) return "";
  const d = new Date(iso);
  return `${pad2(d.getDate())}.${pad2(d.getMonth()+1)}`;
}

function isBreakHour(dateIso: string | undefined, hour: number) {
  if (!dateIso) return false;
  if (hour !== 12) return false;
  const dow = new Date(dateIso).getDay();
  return dow >= 1 && dow <= 5;
}

/* ---------- Конфиг из query ---------- */
type EmbedMode = "minimal" | "compact";
function readEmbedConfig() {
  const sp = new URLSearchParams(window.location.search);
  const modeParam = sp.get("mode") || sp.get("layout");
  const mode: EmbedMode = modeParam === "compact" ? "compact" : "minimal";
  const bg = (sp.get("bg") || sp.get("background") || "").toLowerCase();
  const transparentBg = bg === "transparent" || bg === "none" || sp.get("noBg") === "1";
  const font = (sp.get("font") || "").toLowerCase();
  const panel = (sp.get("panel") ?? (mode === "compact" ? "1" : "0")) !== "0";
  return { mode, transparentBg, font, panel };
}
const EMBED = readEmbedConfig();

/* ---------- Wheel ---------- */
interface WheelProps {
  items: (string | number)[];
  activeIndex: number;
  onChange: (index: number) => void;
  ariaLabel?: string;
  disabledIndices?: Set<number>;
  breakIndices?: Set<number>;
  className?: string;
  itemHeight: number;
  windowRows: number;
  compact?: boolean;
}

const Wheel: React.FC<WheelProps> = ({
  items,
  activeIndex,
  onChange,
  ariaLabel,
  disabledIndices,
  breakIndices,
  className = "",
  itemHeight,
  windowRows,
  compact,
}) => {
  const innerRef = useRef<HTMLDivElement|null>(null);
  const startYRef = useRef<number|null>(null);
  const lastWheelTs = useRef(0);

  // ФИКСИРОВАННАЯ высота строки — чтобы центральные строки в 3-х колесах всегда совпадали.
  const itemH = itemHeight;

  const clamp = (i:number) => Math.max(0, Math.min(items.length - 1, i));

  const shift = (delta:number) => {
    if (!items.length) return;
    let next = clamp(activeIndex + delta);
    if (disabledIndices?.size) {
      while (disabledIndices.has(next) && next !== activeIndex) {
        next = clamp(next + (delta > 0 ? 1 : -1));
      }
    }
    onChange(next);
  };

  // Скролл/тач управление
  useEffect(()=> {
    const el = innerRef.current;
    if (!el) return;

    const onWheel = (e:WheelEvent) => {
      e.preventDefault();
      const now = performance.now();
      if (now - lastWheelTs.current < 70) return;
      lastWheelTs.current = now;
      shift(e.deltaY > 0 ? 1 : -1);
    };
    const onTouchStart = (e:TouchEvent) => { if (e.touches.length) startYRef.current = e.touches[0].clientY; };
    const onTouchMove = (e:TouchEvent) => {
      if (startYRef.current == null) return;
      const dy = e.touches[0].clientY - startYRef.current;
      if (Math.abs(dy) > 18) {
        e.preventDefault();
        shift(dy < 0 ? 1 : -1);
        startYRef.current = e.touches[0].clientY;
      }
    };
    const onTouchEnd = () => { startYRef.current = null; };

    el.addEventListener("wheel", onWheel, { passive:false });
    el.addEventListener("touchstart", onTouchStart, { passive:false });
    el.addEventListener("touchmove", onTouchMove, { passive:false });
    el.addEventListener("touchend", onTouchEnd);

    return ()=> {
      el.removeEventListener("wheel", onWheel);
      el.removeEventListener("touchstart", onTouchStart);
      el.removeEventListener("touchmove", onTouchMove);
      el.removeEventListener("touchend", onTouchEnd);
    };
  }, [activeIndex, items.length, disabledIndices, onChange]);

  const translateY = (itemH * (windowRows/2)) - activeIndex * itemH;
  const windowHeight = itemH * windowRows;

  return (
    <div
      className={"wheel-wrapper "+className+(compact ? " wheel-wrapper--compact": "")}
      aria-label={ariaLabel}
      style={{ height: windowHeight }}
    >
      <div
        className="wheel-inner"
        ref={innerRef}
        style={{ transform:`translateY(${translateY}px)` }}
      >
        {items.map((text, idx)=>{
          const active = idx === activeIndex;
          const disabled = disabledIndices?.has(idx);
          const isBreak = breakIndices?.has(idx);
          return (
            <div
              key={idx+String(text)}
              className={
                "wheel-item" +
                (active ? " wheel-item--active":"") +
                (disabled ? " wheel-item--disabled":"") +
                (isBreak ? " wheel-item--break":"")
              }
              onClick={()=>{ if(!disabled) onChange(idx); }}
              style={{ height: itemH }}
            >{text}</div>
          );
        })}
      </div>
    </div>
  );
};

/* ---------- Полный embed-виджет ---------- */
interface Props { onSelectSlot?: (dateIso: string, hour: number) => void; }

const PoolWheelWidgetEmbed: React.FC<Props> = ({ onSelectSlot }) => {
  const [slots, setSlots] = useState<PoolWorkloadSlot[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string|null>(null);

  const [dateIndex, setDateIndex] = useState(0);
  const [selectedHour, setSelectedHour] = useState<number|null>(null);

  // Подключаем Bebas Neue при font=bebas
  useEffect(() => {
    if (EMBED.font === "bebas") {
      const l1 = document.createElement("link");
      l1.rel = "preconnect"; l1.href = "https://fonts.googleapis.com";
      const l2 = document.createElement("link");
      l2.rel = "preconnect"; l2.href = "https://fonts.gstatic.com"; l2.crossOrigin = "anonymous";
      const l3 = document.createElement("link");
      l3.rel = "stylesheet";
      l3.href = "https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap";
      document.head.appendChild(l1); document.head.appendChild(l2); document.head.appendChild(l3);
      return () => { [l1,l2,l3].forEach(el => el.parentNode?.removeChild(el)); };
    }
  }, []);

  // --- загрузка данных ---
  useEffect(()=> { 
    fetchData(); 
    // eslint-disable-next-line
  }, []);

  const fetchData = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const res: PoolWorkloadResponse = await fetchPoolWorkload({});
      const loaded = res.slots || [];
      setSlots(loaded);

      if (loaded.length) {
        if (selectedHour == null) {
          const first = loaded.map(s=>s.hour).sort((a,b)=>a-b)[0];
          setSelectedHour(first);
        }
      } else {
        if (selectedHour != null) setSelectedHour(null);
      }
    } catch (e:any) {
      setError(e.message || "Ошибка загрузки");
    } finally {
      setLoading(false);
    }
  }, [selectedHour]);

  // --- вычисления для колес ---
  const dates = useMemo(()=>{
    const uniq: string[] = [];
    for (const s of slots) if (!uniq.includes(s.date)) uniq.push(s.date);
    return uniq.sort();
  }, [slots]);

  const currentDate = dates[dateIndex];

  const dateHoursSet = useMemo(()=>{
    const set = new Set<number>();
    slots.forEach(s=> { if (s.date === currentDate) set.add(s.hour); });
    return set;
  }, [slots, currentDate]);

  useEffect(()=> {
    if (!currentDate || selectedHour == null) return;
    if (!dateHoursSet.has(selectedHour)) {
      const sorted = Array.from(dateHoursSet).sort((a,b)=>a-b);
      if (sorted.length) setSelectedHour(sorted[0]);
    }
  }, [currentDate, selectedHour, dateHoursSet]);

  const disabledIndices = useMemo(()=>{
    const set = new Set<number>();
    allHours.forEach((h, idx) => {
      if (!dateHoursSet.has(h)) set.add(idx);
    });
    return set;
  }, [dateHoursSet]);

  const breakIndices = useMemo(()=>{
    const set = new Set<number>();
    if (currentDate) {
      allHours.forEach((h, idx)=> {
        if (isBreakHour(currentDate, h)) set.add(idx);
      });
    }
    return set;
  }, [currentDate]);

  const activeSlot: PoolWorkloadSlot | undefined = useMemo(()=>{
    if (!currentDate || selectedHour == null) return undefined;
    return slots.find(s=> s.date === currentDate && s.hour === selectedHour);
  }, [slots, currentDate, selectedHour]);

  const selectedIsBreak =
    currentDate && selectedHour != null && isBreakHour(currentDate, selectedHour);

  const timeItems = useMemo(()=> allHours.map(h =>
    currentDate && isBreakHour(currentDate, h) ? "перерыв" : `${pad2(h)}:00`
  ), [currentDate]);

  const freePlacesItems = useMemo(()=> allHours.map(h => {
    if (!currentDate || isBreakHour(currentDate, h)) return 0;
    const sl = slots.find(s=> s.date === currentDate && s.hour === h);
    return sl?.freePlaces ?? 0;
  }), [slots, currentDate]);

  const handleDateChange = useCallback((idx:number)=> setDateIndex(idx), []);
  const handleHourChange = useCallback((idx:number)=> setSelectedHour(allHours[idx]), []);

  // --- закомментировано всё, что связано с бронированием ---
  // const handleBook = () => {
  //   if (!onSelectSlot || !activeSlot || selectedIsBreak) return;
  //   onSelectSlot(activeSlot.date, activeSlot.hour);
  // };

  // const canBook = Boolean(activeSlot && !selectedIsBreak);
  const hourIndex = allHours.indexOf(selectedHour ?? allHours[0]);

  // авто-ресайз для iframe
  useEffect(() => {
    const post = () => {
      const h =
        document.documentElement.scrollHeight ||
        document.body.scrollHeight ||
        0;
      // постим всю высоту, чтобы родитель увеличил iframe
      window.parent?.postMessage({ type: "dvvs:wheels:height", height: h }, "*");
    };
    post();
    const ro = new ResizeObserver(post);
    ro.observe(document.documentElement);
    ro.observe(document.body);
    const t = setInterval(post, 800);
    window.addEventListener("load", post);
    return () => {
      ro.disconnect();
      window.removeEventListener("load", post);
      clearInterval(t);
    };
  }, []);

  /* ---------- Ультра-компакт (под ваш контейнер) ---------- */
  const compact = EMBED.mode === "compact";
  const COL_W = compact ? 100 : 260;
  const GAP = compact ? 2 : 36;
  const ITEM_H = compact ? 36 : 60;
  const WINDOW_ROWS = compact ? 3 : 4;
  const LABEL_FS = compact ? 12 : 18;
  const LABEL_M_BOTTOM = compact ? 4 : 8;
  const ITEM_FS = compact ? 18 : 30;
  const BTN_FS = compact ? 15 : 20;
  const BTN_PAD_V = compact ? 10 : 16;
  const BTN_RADIUS = compact ? 20 : 18;

  const BUTTON_GRADIENT = "linear-gradient(90deg, #8F67F0 0%, #4CB5F9 100%)";

  const headerDate = currentDate ? formatHeaderDate(currentDate) : "";
  const headerTime = selectedHour != null ? `${pad2(selectedHour)}:00` : "";

  return (
    <>
      <style>{`
        /* ...твой CSS без изменений... */
      `}</style>

      <div className="pw-embed-root">
        <div className={EMBED.panel ? "pw-panel" : ""}>
          {headerDate && <div className="pw-date-head">{headerDate}</div>}
          {headerTime && <div className="pw-time-sub">{headerTime}</div>}

          {loading && <div style={{ color:"#fff", textAlign:"center", padding: 6, fontSize: 12 }}>Загрузка…</div>}
          {error && <div style={{ color:"#fff", textAlign:"center", padding: 6, fontSize: 12 }}>Ошибка: {error}</div>}

          {!loading && !error && dates.length > 0 && currentDate && (
            <>
              <div className="pw-wheels-row">
                {/* Дата: 01.09 */}
                <div className="pw-wheel-card">
                  <div className="pw-label-above">Дата</div>
                  <Wheel
                    items={dates.map(formatIsoToDDMM)}
                    activeIndex={dateIndex}
                    onChange={handleDateChange}
                    ariaLabel="Дата"
                    itemHeight={ITEM_H}
                    windowRows={WINDOW_ROWS}
                    compact={compact}
                  />
                </div>

                {/* Время */}
                <div className="pw-wheel-card">
                  <div className="pw-label-above">Время</div>
                  <Wheel
                    items={timeItems}
                    activeIndex={hourIndex}
                    onChange={handleHourChange}
                    ariaLabel="Время"
                    disabledIndices={disabledIndices}
                    breakIndices={breakIndices}
                    itemHeight={ITEM_H}
                    windowRows={WINDOW_ROWS}
                    compact={compact}
                  />
                </div>

                {/* Свободно мест */}
                <div className="pw-wheel-card">
                  <div className="pw-label-above">Свободно мест</div>
                  <Wheel
                    items={freePlacesItems.map(v=>String(v))}
                    activeIndex={hourIndex}
                    onChange={()=>{}}
                    ariaLabel="Свободно мест"
                    disabledIndices={disabledIndices}
                    breakIndices={breakIndices}
                    itemHeight={ITEM_H}
                    windowRows={WINDOW_ROWS}
                    compact={compact}
                  />
                </div>
              </div>

              {/* --- Кнопка бронирования и действия — закомментировано --- */}
              {/* <div className="pw-actions">
                <button
                  className="pw-book-btn"
                  disabled={!canBook}
                  onClick={handleBook}
                  title={selectedIsBreak ? "Перерыв" : (!activeSlot ? "Нет данных" : "")}
                >
                  Забронировать
                </button>
              </div> */}
            </>
          )}
        </div>
      </div>
    </>
  );
};

export default PoolWheelWidgetEmbed;
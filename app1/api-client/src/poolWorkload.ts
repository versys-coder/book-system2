import { http } from "./http";

/**
 * Слот загруженности бассейна.
 * Расширили тип под ответ backend /api/pool-workload (freeLanes, isBreak, total*).
 */
export interface PoolWorkloadSlot {
  date: string;        // ISO, напр. 2025-11-04
  hour: number;        // 7..21
  freePlaces: number;  // свободные места (с учётом логики и перерыва)
  // Доп. поля для гистограммы и совместимости
  isBreak?: boolean;
  totalPlaces?: number;
  freeLanes?: number;  // ключ для "цветомузыки" (берём из backend)
  busyLanes?: number;
  totalLanes?: number;
  current?: number | null;
}

export interface PoolWorkloadResponse {
  slots: PoolWorkloadSlot[];
  currentNow?: {
    date: string;
    hour: number;
    current: number | null;
    source: string;
  };
  meta?: {
    serverNowDate: string;
    serverNowHour: number;
    tzOffset: number;
    scheduleMode?: string;
    testRange?: { start: string; end: string };
  };
}

export interface FetchPoolWorkloadParams {
  poolId?: string | number;
  dateFrom?: string; // ISO
  dateTo?: string;   // ISO
  start_hour?: number;
  end_hour?: number;
}

/**
 * Запрашивает загруженность из backend /api/pool-workload.
 */
export async function fetchPoolWorkload(params: FetchPoolWorkloadParams = {}): Promise<PoolWorkloadResponse> {
  const { data } = await http.get<PoolWorkloadResponse>("/api/pool-workload", { params });
  return data;
}
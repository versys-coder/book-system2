import { http } from "./http";

/**
 * Слот загрузки бассейна (минимально, под ваш текущий виджет).
 */
export interface PoolWorkloadSlot {
  date: string;     // ISO, напр. 2025-11-04
  hour: number;     // 7..21
  freePlaces: number;
}

export interface PoolWorkloadResponse {
  slots: PoolWorkloadSlot[];
}

export interface FetchPoolWorkloadParams {
  poolId?: string | number;
  dateFrom?: string; // ISO
  dateTo?: string;   // ISO
}

/**
 * Запрашивает слоты. Подставьте реальный путь вашего backend (schedule-backend).
 * Ожидается, что вернётся массив слотов с полями date, hour, freePlaces.
 */
export async function fetchPoolWorkload(params: FetchPoolWorkloadParams = {}): Promise<PoolWorkloadResponse> {
  const { data } = await http.get<PoolWorkloadResponse>("/api/pool-workload", { params });
  // При необходимости трансформируйте data здесь.
  return data;
}
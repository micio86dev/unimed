import { formatDistanceToNow, parseISO } from 'date-fns'

/** Format seconds as a compact human duration, e.g. "12m 30s" or "1h 05m". */
export function formatDuration(totalSeconds?: number | null): string {
  if (totalSeconds == null) return '—'
  const seconds = Math.max(0, Math.round(totalSeconds))
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = seconds % 60

  if (h > 0) return `${h}h ${String(m).padStart(2, '0')}m`
  if (m > 0) return `${m}m ${String(s).padStart(2, '0')}s`
  return `${s}s`
}

/** Format a 0–100 number as a percentage string. */
export function formatPercent(value?: number | null, digits = 0): string {
  if (value == null) return '—'
  return `${value.toFixed(digits)}%`
}

/** Relative time from an ISO timestamp, e.g. "3 days ago". */
export function formatRelative(iso?: string | null): string {
  if (!iso) return '—'
  try {
    return formatDistanceToNow(parseISO(iso), { addSuffix: true })
  } catch {
    return '—'
  }
}

/** Format an ISO timestamp as a short date, e.g. "12 Jun 2026". */
export function formatDate(iso?: string | null): string {
  if (!iso) return '—'
  try {
    return parseISO(iso).toLocaleDateString('en-GB', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    })
  } catch {
    return '—'
  }
}

/** Get initials from a full name, e.g. "Marco Rossi" → "MR". */
export function initials(name?: string | null): string {
  const trimmed = name?.trim()
  if (!trimmed) return '?'
  return (
    trimmed
      .split(/\s+/)
      .slice(0, 2)
      .map((p) => p[0]?.toUpperCase() ?? '')
      .join('') || '?'
  )
}

/** Tailwind text/badge tone for a score percentage. */
export function scoreTone(percentage?: number | null): 'success' | 'warning' | 'destructive' {
  const v = percentage ?? 0
  if (v >= 70) return 'success'
  if (v >= 50) return 'warning'
  return 'destructive'
}

/** Literal Tailwind text-colour class for a score (kept literal so it is scanned). */
export function scoreTextClass(percentage?: number | null): string {
  const tone = scoreTone(percentage)
  return tone === 'success'
    ? 'text-success'
    : tone === 'warning'
      ? 'text-warning'
      : 'text-destructive'
}

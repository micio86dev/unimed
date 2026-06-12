<script setup lang="ts">
import { Crown, Medal, Trophy } from 'lucide-vue-next'
import type { Paginated, Ranking } from '~/types'

definePageMeta({ middleware: 'auth' })

const auth = useAuthStore()

const { data: board, pending } = await useAsyncData('rankings', () =>
  useApi()<Paginated<Ranking>>('/rankings', { query: { per_page: 50 } }),
)
const { data: me } = await useAsyncData('rankings-me', () =>
  useApi()<{ data: { ranking: Ranking | null; total_participants: number } }>('/rankings/me').then(
    (r) => r.data,
  ),
)

const podiumStyle: Record<number, string> = {
  1: 'text-warning',
  2: 'text-muted-foreground',
  3: 'text-[#b45309]',
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader title="Leaderboard" description="See how you stack up against other candidates." />

    <!-- My position -->
    <Card v-if="me?.ranking" class="bg-primary text-primary-foreground">
      <CardContent class="flex flex-wrap items-center justify-between gap-4 p-6">
        <div class="flex items-center gap-4">
          <div class="flex size-14 items-center justify-center rounded-xl bg-white/15 text-2xl font-bold tabular-nums">
            #{{ me.ranking.position }}
          </div>
          <div>
            <p class="text-sm text-primary-foreground/80">Your position</p>
            <p class="text-lg font-semibold">{{ auth.user?.name }}</p>
          </div>
        </div>
        <div class="flex gap-6 text-center">
          <div>
            <p class="text-2xl font-bold tabular-nums">{{ me.ranking.total_points }}</p>
            <p class="text-xs text-primary-foreground/80">Points</p>
          </div>
          <div>
            <p class="text-2xl font-bold tabular-nums">{{ formatPercent(me.ranking.average_score, 1) }}</p>
            <p class="text-xs text-primary-foreground/80">Avg score</p>
          </div>
          <div>
            <p class="text-2xl font-bold tabular-nums">{{ me.ranking.quizzes_completed }}</p>
            <p class="text-xs text-primary-foreground/80">Quizzes</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardContent class="p-0">
        <div v-if="pending" class="space-y-2 p-4">
          <Skeleton v-for="i in 8" :key="i" class="h-12" />
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-border text-left text-xs uppercase tracking-wider text-muted-foreground">
                <th class="px-4 py-3 font-medium sm:px-6">Rank</th>
                <th class="px-4 py-3 font-medium sm:px-6">Student</th>
                <th class="px-4 py-3 text-right font-medium sm:px-6">Points</th>
                <th class="hidden px-4 py-3 text-right font-medium sm:table-cell sm:px-6">Avg score</th>
                <th class="hidden px-4 py-3 text-right font-medium md:table-cell sm:px-6">Best</th>
                <th class="hidden px-4 py-3 text-right font-medium lg:table-cell sm:px-6">Quizzes</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in board?.data"
                :key="row.user_id"
                class="border-b border-border last:border-0 transition-colors"
                :class="row.is_current_user ? 'bg-primary/5' : 'hover:bg-muted/40'"
              >
                <td class="px-4 py-3 sm:px-6">
                  <span class="flex items-center gap-1.5 font-semibold tabular-nums">
                    <Crown v-if="row.position === 1" :class="['size-4', podiumStyle[1]]" />
                    <Medal v-else-if="row.position === 2 || row.position === 3" :class="['size-4', podiumStyle[row.position]]" />
                    <Trophy v-else class="size-4 text-transparent" />
                    {{ row.position }}
                  </span>
                </td>
                <td class="px-4 py-3 sm:px-6">
                  <div class="flex items-center gap-3">
                    <Avatar :name="row.name" class="size-8" />
                    <span class="font-medium">
                      {{ row.name }}
                      <Badge v-if="row.is_current_user" variant="default" class="ml-1">You</Badge>
                    </span>
                  </div>
                </td>
                <td class="px-4 py-3 text-right font-semibold tabular-nums sm:px-6">{{ row.total_points }}</td>
                <td class="hidden px-4 py-3 text-right tabular-nums text-muted-foreground sm:table-cell sm:px-6">{{ formatPercent(row.average_score, 1) }}</td>
                <td class="hidden px-4 py-3 text-right tabular-nums text-muted-foreground md:table-cell sm:px-6">{{ formatPercent(row.best_score, 0) }}</td>
                <td class="hidden px-4 py-3 text-right tabular-nums text-muted-foreground lg:table-cell sm:px-6">{{ row.quizzes_completed }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

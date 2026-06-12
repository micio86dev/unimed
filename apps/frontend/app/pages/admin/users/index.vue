<script setup lang="ts">
import { Ban, CircleCheck, Pencil, Plus, Search, Trash2, Users } from 'lucide-vue-next'
import type { Paginated, User } from '~/types'

definePageMeta({ layout: 'admin', middleware: 'admin' })

const auth = useAuthStore()
const { t } = useI18n()
const { success, error: toastError } = useToast()

const search = ref('')
const role = ref('')
const active = ref('')
const page = ref(1)
const debouncedSearch = refDebounced(search, 300)
watch([debouncedSearch, role, active], () => {
  page.value = 1
})

const query = computed(() => ({
  search: debouncedSearch.value || undefined,
  role: role.value || undefined,
  is_active: active.value || undefined,
  page: page.value,
  per_page: 15,
}))

const { data, pending, refresh } = await useAsyncData(
  'admin-users',
  () => useApi()<Paginated<User>>('/admin/users', { query: query.value }),
  { watch: [query] },
)

const modalOpen = ref(false)
const editing = ref<User | null>(null)
const deleteTarget = ref<User | null>(null)
const deleting = ref(false)
const togglingId = ref<number | null>(null)

function openCreate() {
  editing.value = null
  modalOpen.value = true
}
function openEdit(u: User) {
  editing.value = u
  modalOpen.value = true
}

async function toggleActive(u: User) {
  togglingId.value = u.id
  try {
    await useApi()(`/admin/users/${u.id}/toggle-active`, { method: 'PATCH' })
    success(u.is_active ? t('admin.userDisabled') : t('admin.userEnabled'))
    await refresh()
  } catch (e) {
    toastError(t('admin.couldNotUpdate'), apiErrorMessage(e))
  } finally {
    togglingId.value = null
  }
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await useApi()(`/admin/users/${deleteTarget.value.id}`, { method: 'DELETE' })
    success(t('admin.userDeleted'))
    deleteTarget.value = null
    await refresh()
  } catch (e) {
    toastError(t('admin.couldNotDelete'), apiErrorMessage(e))
  } finally {
    deleting.value = false
  }
}

const roleOptions = computed(() => [
  { label: t('admin.allRoles'), value: '' },
  { label: t('admin.students'), value: 'student' },
  { label: t('admin.admins'), value: 'admin' },
])
const activeOptions = computed(() => [
  { label: t('admin.allStatuses'), value: '' },
  { label: t('admin.active'), value: '1' },
  { label: t('admin.disabled'), value: '0' },
])
</script>

<template>
  <div class="space-y-6">
    <PageHeader :title="$t('admin.usersTitle')" :description="$t('admin.usersSubtitle')">
      <template #actions>
        <Button @click="openCreate"><Plus class="size-4" /> {{ $t('admin.newUser') }}</Button>
      </template>
    </PageHeader>

    <div class="flex flex-col gap-3 sm:flex-row">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
        <Input v-model="search" :placeholder="$t('admin.searchUsers')" class="pl-9" />
      </div>
      <Select v-model="role" :options="roleOptions" class="sm:w-40" />
      <Select v-model="active" :options="activeOptions" class="sm:w-40" />
    </div>

    <Card>
      <CardContent class="p-0">
        <div v-if="pending" class="space-y-2 p-4"><Skeleton v-for="i in 8" :key="i" class="h-14" /></div>
        <EmptyState v-else-if="!data?.data.length" :icon="Users" :title="$t('admin.noStudents')" :description="$t('admin.noStudentsDesc')" class="m-6" />
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-border text-left text-xs uppercase tracking-wider text-muted-foreground">
                <th class="px-4 py-3 font-medium sm:px-6">{{ $t('admin.colUser') }}</th>
                <th class="hidden px-4 py-3 font-medium md:table-cell sm:px-6">{{ $t('admin.colRole') }}</th>
                <th class="hidden px-4 py-3 font-medium lg:table-cell sm:px-6">{{ $t('admin.colLastLogin') }}</th>
                <th class="px-4 py-3 font-medium sm:px-6">{{ $t('admin.colStatus') }}</th>
                <th class="px-4 py-3 text-right font-medium sm:px-6">{{ $t('admin.colActions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in data.data" :key="u.id" class="border-b border-border last:border-0 hover:bg-muted/40">
                <td class="px-4 py-3 sm:px-6">
                  <div class="flex items-center gap-3">
                    <Avatar :name="u.name" :src="u.avatar_url" class="size-9" />
                    <div class="min-w-0">
                      <p class="truncate font-medium">{{ u.name }}</p>
                      <p class="truncate text-xs text-muted-foreground">{{ u.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="hidden px-4 py-3 md:table-cell sm:px-6">
                  <Badge :variant="u.roles.includes('admin') ? 'default' : 'secondary'">{{ u.roles.includes('admin') ? $t('nav.admin') : $t('nav.student') }}</Badge>
                </td>
                <td class="hidden px-4 py-3 text-muted-foreground lg:table-cell sm:px-6">{{ formatRelative(u.last_login_at) }}</td>
                <td class="px-4 py-3 sm:px-6">
                  <Badge :variant="u.is_active ? 'success' : 'outline'">{{ u.is_active ? $t('admin.active') : $t('admin.disabled') }}</Badge>
                </td>
                <td class="px-4 py-3 sm:px-6">
                  <div class="flex items-center justify-end gap-1">
                    <button class="cursor-pointer rounded-md p-2 text-muted-foreground hover:bg-muted hover:text-foreground" :title="$t('admin.edit')" @click="openEdit(u)">
                      <Pencil class="size-4" />
                    </button>
                    <button
                      v-if="u.id !== auth.user?.id"
                      class="cursor-pointer rounded-md p-2 text-muted-foreground hover:bg-muted"
                      :title="u.is_active ? $t('admin.disable') : $t('admin.enable')"
                      :disabled="togglingId === u.id"
                      @click="toggleActive(u)"
                    >
                      <Ban v-if="u.is_active" class="size-4 hover:text-warning" />
                      <CircleCheck v-else class="size-4 hover:text-success" />
                    </button>
                    <button
                      v-if="u.id !== auth.user?.id"
                      class="cursor-pointer rounded-md p-2 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                      :title="$t('admin.delete')"
                      @click="deleteTarget = u"
                    >
                      <Trash2 class="size-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>

    <div v-if="data && data.meta.last_page > 1" class="flex items-center justify-between">
      <p class="text-sm text-muted-foreground">{{ $t('admin.usersCountLabel', { total: data.meta.total, current: data.meta.current_page, pages: data.meta.last_page }) }}</p>
      <div class="flex gap-2">
        <Button variant="outline" size="sm" :disabled="page <= 1" @click="page--">{{ $t('common.previous') }}</Button>
        <Button variant="outline" size="sm" :disabled="page >= data.meta.last_page" @click="page++">{{ $t('common.next') }}</Button>
      </div>
    </div>

    <AdminUserFormModal v-model:open="modalOpen" :user="editing" @saved="refresh" />

    <Modal :open="!!deleteTarget" :title="$t('admin.deleteUserTitle')" :description="$t('admin.deleteUserDesc')" @update:open="(v) => !v && (deleteTarget = null)">
      <p class="rounded-lg bg-muted/50 p-3 text-sm font-medium">{{ deleteTarget?.name }} · {{ deleteTarget?.email }}</p>
      <div class="mt-5 flex justify-end gap-2">
        <Button variant="outline" @click="deleteTarget = null">{{ $t('admin.cancel') }}</Button>
        <Button variant="destructive" :loading="deleting" @click="confirmDelete">{{ $t('admin.delete') }}</Button>
      </div>
    </Modal>
  </div>
</template>

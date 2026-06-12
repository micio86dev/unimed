<script setup lang="ts">
import { Ban, CircleCheck, Pencil, Plus, Search, Trash2, Users } from 'lucide-vue-next'
import type { Paginated, User } from '~/types'

definePageMeta({ layout: 'admin', middleware: 'admin' })

const auth = useAuthStore()
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
    success(u.is_active ? 'User disabled' : 'User enabled')
    await refresh()
  } catch (e) {
    toastError('Could not update', apiErrorMessage(e))
  } finally {
    togglingId.value = null
  }
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await useApi()(`/admin/users/${deleteTarget.value.id}`, { method: 'DELETE' })
    success('User deleted')
    deleteTarget.value = null
    await refresh()
  } catch (e) {
    toastError('Could not delete', apiErrorMessage(e))
  } finally {
    deleting.value = false
  }
}

const roleOptions = [
  { label: 'All roles', value: '' },
  { label: 'Students', value: 'student' },
  { label: 'Admins', value: 'admin' },
]
const activeOptions = [
  { label: 'All statuses', value: '' },
  { label: 'Active', value: '1' },
  { label: 'Disabled', value: '0' },
]
</script>

<template>
  <div class="space-y-6">
    <PageHeader title="Users" description="Manage students and administrators.">
      <template #actions>
        <Button @click="openCreate"><Plus class="size-4" /> New user</Button>
      </template>
    </PageHeader>

    <div class="flex flex-col gap-3 sm:flex-row">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
        <Input v-model="search" placeholder="Search by name or email…" class="pl-9" />
      </div>
      <Select v-model="role" :options="roleOptions" class="sm:w-40" />
      <Select v-model="active" :options="activeOptions" class="sm:w-40" />
    </div>

    <Card>
      <CardContent class="p-0">
        <div v-if="pending" class="space-y-2 p-4"><Skeleton v-for="i in 8" :key="i" class="h-14" /></div>
        <EmptyState v-else-if="!data?.data.length" :icon="Users" title="No users found" description="Try adjusting your filters." class="m-6" />
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-border text-left text-xs uppercase tracking-wider text-muted-foreground">
                <th class="px-4 py-3 font-medium sm:px-6">User</th>
                <th class="hidden px-4 py-3 font-medium md:table-cell sm:px-6">Role</th>
                <th class="hidden px-4 py-3 font-medium lg:table-cell sm:px-6">Last login</th>
                <th class="px-4 py-3 font-medium sm:px-6">Status</th>
                <th class="px-4 py-3 text-right font-medium sm:px-6">Actions</th>
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
                  <Badge :variant="u.roles.includes('admin') ? 'default' : 'secondary'" class="capitalize">{{ u.roles[0] }}</Badge>
                </td>
                <td class="hidden px-4 py-3 text-muted-foreground lg:table-cell sm:px-6">{{ formatRelative(u.last_login_at) }}</td>
                <td class="px-4 py-3 sm:px-6">
                  <Badge :variant="u.is_active ? 'success' : 'outline'">{{ u.is_active ? 'Active' : 'Disabled' }}</Badge>
                </td>
                <td class="px-4 py-3 sm:px-6">
                  <div class="flex items-center justify-end gap-1">
                    <button class="rounded-md p-2 text-muted-foreground hover:bg-muted hover:text-foreground" title="Edit" @click="openEdit(u)">
                      <Pencil class="size-4" />
                    </button>
                    <button
                      v-if="u.id !== auth.user?.id"
                      class="rounded-md p-2 text-muted-foreground hover:bg-muted"
                      :title="u.is_active ? 'Disable' : 'Enable'"
                      :disabled="togglingId === u.id"
                      @click="toggleActive(u)"
                    >
                      <Ban v-if="u.is_active" class="size-4 hover:text-warning" />
                      <CircleCheck v-else class="size-4 hover:text-success" />
                    </button>
                    <button
                      v-if="u.id !== auth.user?.id"
                      class="rounded-md p-2 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                      title="Delete"
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
      <p class="text-sm text-muted-foreground">{{ data.meta.total }} users · page {{ data.meta.current_page }} of {{ data.meta.last_page }}</p>
      <div class="flex gap-2">
        <Button variant="outline" size="sm" :disabled="page <= 1" @click="page--">Previous</Button>
        <Button variant="outline" size="sm" :disabled="page >= data.meta.last_page" @click="page++">Next</Button>
      </div>
    </div>

    <AdminUserFormModal v-model:open="modalOpen" :user="editing" @saved="refresh" />

    <Modal :open="!!deleteTarget" title="Delete user?" description="This permanently removes the account." @update:open="(v) => !v && (deleteTarget = null)">
      <p class="rounded-lg bg-muted/50 p-3 text-sm font-medium">{{ deleteTarget?.name }} · {{ deleteTarget?.email }}</p>
      <div class="mt-5 flex justify-end gap-2">
        <Button variant="outline" @click="deleteTarget = null">Cancel</Button>
        <Button variant="destructive" :loading="deleting" @click="confirmDelete">Delete</Button>
      </div>
    </Modal>
  </div>
</template>

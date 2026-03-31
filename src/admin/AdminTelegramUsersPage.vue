<template>
  <div class="tg-admin-page">
    <header class="head">
      <div>
        <h1 class="title">Telegram-бот</h1>
        <p class="subtitle">Пользователи бота, согласие с политикой конфиденциальности и история действий</p>
      </div>
      <button class="refreshBtn" :disabled="loading" @click="loadData">
        {{ loading ? 'Обновление...' : 'Обновить' }}
      </button>
    </header>

    <section class="statsGrid">
      <article class="statCard">
        <div class="statLabel">Всего пользователей</div>
        <div class="statValue">{{ stats.total_users }}</div>
      </article>
      <article class="statCard">
        <div class="statLabel">Приняли политику</div>
        <div class="statValue ok">{{ stats.accepted_users }}</div>
      </article>
      <article class="statCard">
        <div class="statLabel">Без согласия</div>
        <div class="statValue warn">{{ stats.pending_users }}</div>
      </article>
      <article class="statCard">
        <div class="statLabel">Действий за 24 часа</div>
        <div class="statValue">{{ stats.actions_24h }}</div>
      </article>
    </section>

    <section class="panel">
      <div class="panelHead">
        <div>
          <h2 class="panelTitle">Пользователи</h2>
          <p class="panelSub">Кто пользуется ботом и что делал последним</p>
        </div>
        <input
          v-model.trim="filterText"
          class="searchInput"
          type="text"
          placeholder="Поиск по username, имени, chat_id"
        />
      </div>

      <div v-if="error" class="errorBox">{{ error }}</div>
      <div v-else-if="loading" class="emptyBox">Загрузка данных...</div>
      <div v-else-if="!filteredUsers.length" class="emptyBox">Пользователи пока не найдены.</div>
      <div v-else class="tableWrap">
        <table class="dataTable">
          <thead>
            <tr>
              <th>Пользователь</th>
              <th>Chat ID</th>
              <th>Согласие</th>
              <th>Последнее действие</th>
              <th>Действий</th>
              <th>Последняя активность</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in filteredUsers" :key="user.chat_id">
              <td>
                <div class="userMain">{{ formatUserName(user) }}</div>
                <div v-if="user.username" class="userSub">@{{ user.username }}</div>
                <div v-if="user.details_text" class="detailText">{{ user.details_text }}</div>
              </td>
              <td class="mono">{{ user.chat_id }}</td>
              <td>
                <span class="badge" :class="user.consent_accepted ? 'ok' : 'warn'">
                  {{ user.consent_accepted ? 'Принято' : 'Не принято' }}
                </span>
                <div v-if="user.consent_at" class="subDate">{{ formatDate(user.consent_at) }}</div>
              </td>
              <td>
                <div class="actionMain">{{ user.last_action_label || 'Нет действий' }}</div>
                <div v-if="user.last_action" class="userSub mono">{{ user.last_action }}</div>
              </td>
              <td>{{ user.total_actions }}</td>
              <td>{{ formatDate(user.updated_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="panel">
      <div class="panelHead simple">
        <div>
          <h2 class="panelTitle">Последние действия</h2>
          <p class="panelSub">Журнал действий внутри Telegram-бота</p>
        </div>
      </div>

      <div v-if="error" class="errorBox">{{ error }}</div>
      <div v-else-if="loading" class="emptyBox">Загрузка данных...</div>
      <div v-else-if="!actions.length" class="emptyBox">История действий пока пуста.</div>
      <div v-else class="tableWrap">
        <table class="dataTable">
          <thead>
            <tr>
              <th>Когда</th>
              <th>Пользователь</th>
              <th>Действие</th>
              <th>Детали</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="action in actions" :key="action.id">
              <td>{{ formatDate(action.created_at) }}</td>
              <td>
                <div class="userMain">{{ formatUserName(action) }}</div>
                <div v-if="action.username" class="userSub">@{{ action.username }}</div>
                <div class="userSub mono">{{ action.chat_id }}</div>
              </td>
              <td>
                <div class="actionMain">{{ action.action_label }}</div>
                <div class="userSub mono">{{ action.action_code }}</div>
              </td>
              <td>{{ action.details_text || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useHead } from '@vueuse/head'

useHead({
  title: 'Telegram-бот - админ-панель',
  meta: [{ name: 'robots', content: 'noindex,nofollow' }],
})

const loading = ref(false)
const error = ref('')
const filterText = ref('')
const stats = ref({
  total_users: 0,
  accepted_users: 0,
  pending_users: 0,
  actions_total: 0,
  actions_24h: 0,
})
const users = ref([])
const actions = ref([])

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(String(value).replace(' ', 'T'))
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('ru-RU', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

function formatUserName(row) {
  const fullName = [row.first_name, row.last_name].filter(Boolean).join(' ').trim()
  if (fullName) return fullName
  if (row.username) return `@${row.username}`
  if (row.user_id) return `User ${row.user_id}`
  return 'Без имени'
}

const filteredUsers = computed(() => {
  const q = filterText.value.trim().toLowerCase()
  if (!q) return users.value

  return users.value.filter((user) => {
    const haystack = [
      user.username,
      user.first_name,
      user.last_name,
      user.chat_id,
      user.user_id,
      user.last_action,
      user.last_action_label,
      user.details_text,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    return haystack.includes(q)
  })
})

async function loadData() {
  loading.value = true
  error.value = ''
  try {
    const res = await fetch('/api/admin/telegram/get_dashboard.php', {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    })
    const data = await res.json().catch(() => null)
    if (!res.ok || !data?.ok) {
      throw new Error(data?.error || 'Не удалось загрузить данные Telegram-бота')
    }

    stats.value = { ...stats.value, ...(data.stats || {}) }
    users.value = Array.isArray(data.users) ? data.users : []
    actions.value = Array.isArray(data.actions) ? data.actions : []
  } catch (err) {
    error.value = err?.message || 'Не удалось загрузить данные'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
.tg-admin-page {
  min-height: 100dvh;
  padding: clamp(16px, 3vw, 28px);
  background: var(--bg-main);
  color: var(--text-main);
}

.head,
.panel {
  max-width: 1240px;
  margin: 0 auto 16px;
}

.head {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  align-items: flex-start;
}

.title {
  margin: 0;
  font-size: clamp(24px, 2.3vw, 32px);
}

.subtitle,
.panelSub {
  margin: 6px 0 0;
  color: var(--text-muted);
  line-height: 1.45;
}

.refreshBtn {
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  color: var(--text-main);
  border-radius: var(--radius-md);
  padding: 10px 14px;
  cursor: pointer;
  font-weight: 700;
}

.refreshBtn:disabled {
  opacity: .65;
  cursor: default;
}

.statsGrid {
  max-width: 1240px;
  margin: 0 auto 16px;
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.statCard,
.panel {
  background: var(--bg-panel);
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}

.statCard {
  padding: 16px;
}

.statLabel {
  color: var(--text-muted);
  font-size: 13px;
}

.statValue {
  margin-top: 8px;
  font-size: 28px;
  font-weight: 800;
}

.statValue.ok,
.badge.ok {
  color: #198754;
}

.statValue.warn,
.badge.warn {
  color: #c27a00;
}

.panel {
  padding: 16px;
}

.panelHead {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  align-items: end;
  margin-bottom: 14px;
}

.panelHead.simple {
  align-items: flex-start;
}

.panelTitle {
  margin: 0;
  font-size: 18px;
}

.searchInput {
  width: min(340px, 100%);
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
  color: var(--text-main);
  border-radius: var(--radius-md);
  padding: 10px 12px;
}

.tableWrap {
  overflow: auto;
}

.dataTable {
  width: 100%;
  border-collapse: collapse;
  min-width: 920px;
}

.dataTable th,
.dataTable td {
  padding: 12px 10px;
  border-top: 1px solid var(--border-soft);
  vertical-align: top;
  text-align: left;
}

.dataTable thead th {
  border-top: none;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 700;
}

.userMain,
.actionMain {
  font-weight: 700;
}

.userSub,
.subDate,
.detailText {
  margin-top: 4px;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.4;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 700;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace;
}

.errorBox,
.emptyBox {
  border: 1px dashed var(--border-soft);
  border-radius: var(--radius-md);
  padding: 16px;
  color: var(--text-muted);
  background: var(--bg-soft);
}

.errorBox {
  color: #b42318;
}

@media (max-width: 860px) {
  .head,
  .panelHead {
    flex-direction: column;
    align-items: stretch;
  }

  .searchInput {
    width: 100%;
  }
}
</style>

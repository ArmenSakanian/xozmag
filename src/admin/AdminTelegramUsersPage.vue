<template>
  <div class="tg-admin-page">
    <header class="head">
      <div>
        <h1 class="title">Telegram-бот</h1>
        <p class="subtitle">Пользователи первого бота, журнал действий и раздел техподдержки</p>
      </div>
      <button class="refreshBtn" :disabled="loading" @click="loadData(selectedChatId, selectedSupportChatId)">
        {{ loading ? 'Обновление...' : 'Обновить' }}
      </button>
    </header>

    <section class="statsGrid">
      <article class="statCard">
        <div class="statLabel">Пользователей первого бота</div>
        <div class="statValue">{{ stats.total_users }}</div>
      </article>
      <article class="statCard">
        <div class="statLabel">Приняли политику</div>
        <div class="statValue ok">{{ stats.accepted_users }}</div>
      </article>
      <article class="statCard">
        <div class="statLabel">Действий за 24 часа</div>
        <div class="statValue">{{ stats.actions_24h }}</div>
      </article>
      <article class="statCard">
        <div class="statLabel">Диалогов техподдержки</div>
        <div class="statValue">{{ supportStats.total_threads }}</div>
      </article>
      <article class="statCard">
        <div class="statLabel">Ждут просмотра</div>
        <div class="statValue warn">{{ supportStats.unread_threads }}</div>
      </article>
      <article class="statCard">
        <div class="statLabel">Новых сообщений</div>
        <div class="statValue warn">{{ supportStats.unread_messages }}</div>
      </article>
    </section>

    <section class="panel">
      <div class="panelHead">
        <div>
          <h2 class="panelTitle">Пользователи первого бота</h2>
          <p class="panelSub">Нажмите на плитку, чтобы открыть историю конкретного пользователя</p>
        </div>
        <input v-model.trim="filterText" class="searchInput" type="text" placeholder="Поиск по username, имени, chat_id" />
      </div>

      <div v-if="error" class="errorBox">{{ error }}</div>
      <div v-else-if="loading && !users.length" class="emptyBox">Загрузка данных...</div>
      <div v-else-if="!filteredUsers.length" class="emptyBox">Пользователи пока не найдены.</div>
      <div v-else class="tileGrid">
        <button
          v-for="user in filteredUsers"
          :key="`user-${user.chat_id}`"
          type="button"
          class="infoTile"
          :class="{ active: String(user.chat_id) === selectedChatId }"
          @click="selectUser(user.chat_id)"
        >
          <div class="tileTop">
            <div>
              <div class="itemMain">{{ formatUserName(user) }}</div>
              <div v-if="user.username" class="itemSub">@{{ user.username }}</div>
              <div class="itemSub mono">{{ user.chat_id }}</div>
            </div>
            <span class="badge" :class="user.consent_accepted ? 'ok' : 'warn'">
              {{ user.consent_accepted ? 'Принято' : 'Не принято' }}
            </span>
          </div>

          <div class="tileMetaRow">
            <span class="metaLabel">Последнее действие</span>
            <span class="metaValue">{{ user.last_action_label || 'Нет действий' }}</span>
          </div>
          <div v-if="user.details_text" class="detailText">{{ user.details_text }}</div>

          <div class="tileFoot">
            <div>
              <div class="metaLabel">Всего действий</div>
              <div class="metaValue strong">{{ user.total_actions }}</div>
            </div>
            <div class="tileRight">
              <div class="metaLabel">Последняя активность</div>
              <div class="metaValue">{{ formatDate(user.updated_at) }}</div>
            </div>
          </div>
        </button>
      </div>
    </section>

    <section class="panel">
      <div class="panelHead">
        <div>
          <h2 class="panelTitle">История первого бота</h2>
          <p class="panelSub">
            {{ selectedUser ? 'Журнал действий выбранного пользователя' : 'Общий журнал последних действий первого бота' }}
          </p>
        </div>
        <button v-if="selectedUser" type="button" class="ghostBtn" @click="showGlobalActions">Показать общий журнал</button>
      </div>

      <div v-if="selectedUser" class="selectedCard">
        <div class="selectedMain">
          <div>
            <div class="selectedName">{{ formatUserName(selectedUser) }}</div>
            <div v-if="selectedUser.username" class="itemSub">@{{ selectedUser.username }}</div>
            <div class="itemSub mono">Chat ID: {{ selectedUser.chat_id }}</div>
          </div>
          <span class="badge" :class="selectedUser.consent_accepted ? 'ok' : 'warn'">
            {{ selectedUser.consent_accepted ? 'Политика принята' : 'Политика не принята' }}
          </span>
        </div>

        <div class="selectedMetaGrid">
          <div>
            <div class="metaLabel">Дата согласия</div>
            <div class="metaValue">{{ formatDate(selectedUser.consent_at) }}</div>
          </div>
          <div>
            <div class="metaLabel">Последнее действие</div>
            <div class="metaValue">{{ selectedUser.last_action_label || '-' }}</div>
          </div>
          <div>
            <div class="metaLabel">Всего действий</div>
            <div class="metaValue">{{ selectedUser.total_actions }}</div>
          </div>
          <div>
            <div class="metaLabel">Последняя активность</div>
            <div class="metaValue">{{ formatDate(selectedUser.updated_at) }}</div>
          </div>
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
              <th>{{ selectedUser ? 'Действие' : 'Пользователь' }}</th>
              <th>{{ selectedUser ? 'Код действия' : 'Действие' }}</th>
              <th>Детали</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="action in actions" :key="action.id">
              <td>{{ formatDate(action.created_at) }}</td>
              <td v-if="selectedUser">
                <div class="itemMain">{{ action.action_label }}</div>
              </td>
              <td v-else>
                <div class="itemMain">{{ formatUserName(action) }}</div>
                <div v-if="action.username" class="itemSub">@{{ action.username }}</div>
                <div class="itemSub mono">{{ action.chat_id }}</div>
              </td>
              <td v-if="selectedUser" class="mono">{{ action.action_code }}</td>
              <td v-else>
                <div class="itemMain">{{ action.action_label }}</div>
                <div class="itemSub mono">{{ action.action_code }}</div>
              </td>
              <td>{{ action.details_text || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="panel">
      <div class="panelHead">
        <div>
          <h2 class="panelTitle">Техподдержка</h2>
          <p class="panelSub">Плитки показывают пользователей второго бота. Непрочитанные обращения помечены счётчиком.</p>
        </div>
        <input v-model.trim="supportFilterText" class="searchInput" type="text" placeholder="Поиск по username, имени, chat_id" />
      </div>

      <div v-if="!supportEnabled" class="emptyBox">Файлы второго бота техподдержки ещё не подключены.</div>
      <template v-else>
        <div v-if="!filteredSupportThreads.length" class="emptyBox">Обращений в техподдержку пока нет.</div>
        <div v-else class="tileGrid">
          <button
            v-for="thread in filteredSupportThreads"
            :key="`support-${thread.chat_id}`"
            type="button"
            class="infoTile"
            :class="{ active: String(thread.chat_id) === selectedSupportChatId }"
            @click="selectSupportThread(thread.chat_id)"
          >
            <div class="tileTop">
              <div>
                <div class="itemMain">{{ formatUserName(thread) }}</div>
                <div v-if="thread.username" class="itemSub">@{{ thread.username }}</div>
                <div class="itemSub mono">{{ thread.chat_id }}</div>
              </div>
              <span v-if="Number(thread.unread_count) > 0" class="counterBadge">{{ thread.unread_count }}</span>
            </div>

            <div class="tileMetaRow">
              <span class="metaLabel">Последнее сообщение</span>
              <span class="metaValue">{{ formatDate(thread.last_user_message_at || thread.last_message_at) }}</span>
            </div>
            <div class="detailText">{{ thread.last_message_preview || 'Сообщение отсутствует' }}</div>
          </button>
        </div>
      </template>
    </section>

    <section class="panel">
      <div class="panelHead">
        <div>
          <h2 class="panelTitle">История техподдержки</h2>
          <p class="panelSub">
            {{ selectedSupportThread ? 'История сообщений выбранного пользователя' : 'Выберите плитку пользователя выше, чтобы открыть переписку' }}
          </p>
        </div>
      </div>

      <div v-if="selectedSupportThread" class="selectedCard">
        <div class="selectedMain">
          <div>
            <div class="selectedName">{{ formatUserName(selectedSupportThread) }}</div>
            <div v-if="selectedSupportThread.username" class="itemSub">@{{ selectedSupportThread.username }}</div>
            <div class="itemSub mono">Chat ID: {{ selectedSupportThread.chat_id }}</div>
          </div>
          <span class="badge" :class="Number(selectedSupportThread.unread_count) > 0 ? 'warn' : 'ok'">
            {{ Number(selectedSupportThread.unread_count) > 0 ? 'Есть непрочитанные' : 'Все просмотрено' }}
          </span>
        </div>

        <div class="selectedMetaGrid">
          <div>
            <div class="metaLabel">Последнее сообщение</div>
            <div class="metaValue">{{ formatDate(selectedSupportThread.last_user_message_at || selectedSupportThread.last_message_at) }}</div>
          </div>
          <div>
            <div class="metaLabel">Создан диалог</div>
            <div class="metaValue">{{ formatDate(selectedSupportThread.created_at) }}</div>
          </div>
          <div>
            <div class="metaLabel">Непрочитанных было</div>
            <div class="metaValue">{{ selectedSupportThread.unread_count }}</div>
          </div>
        </div>
      </div>

      <div v-if="supportEnabled && selectedSupportThread && !supportMessages.length" class="emptyBox">История сообщений пока пуста.</div>
      <div v-else-if="supportEnabled && selectedSupportThread" class="supportThreadWrap">
        <article v-for="message in supportMessagesOrdered" :key="message.id" class="messageCard">
          <div class="messageMeta">
            <span class="badge ok">Пользователь</span>
            <span>{{ formatDate(message.created_at) }}</span>
          </div>
          <div class="messageText">{{ message.message_text }}</div>
        </article>
      </div>
      <div v-else class="emptyBox">Выберите диалог техподдержки выше.</div>
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
const supportFilterText = ref('')
const selectedChatId = ref('')
const selectedSupportChatId = ref('')
const selectedUser = ref(null)
const selectedSupportThread = ref(null)
const supportEnabled = ref(false)

const stats = ref({
  total_users: 0,
  accepted_users: 0,
  pending_users: 0,
  actions_total: 0,
  actions_24h: 0,
})
const supportStats = ref({
  total_threads: 0,
  unread_threads: 0,
  unread_messages: 0,
  messages_total: 0,
})
const users = ref([])
const actions = ref([])
const supportThreads = ref([])
const supportMessages = ref([])

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
  const fullName = [row?.first_name, row?.last_name].filter(Boolean).join(' ').trim()
  if (fullName) return fullName
  if (row?.username) return `@${row.username}`
  if (row?.user_id) return `User ${row.user_id}`
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

const filteredSupportThreads = computed(() => {
  const q = supportFilterText.value.trim().toLowerCase()
  if (!q) return supportThreads.value

  return supportThreads.value.filter((thread) => {
    const haystack = [
      thread.username,
      thread.first_name,
      thread.last_name,
      thread.chat_id,
      thread.user_id,
      thread.last_message_preview,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    return haystack.includes(q)
  })
})

const supportMessagesOrdered = computed(() => [...supportMessages.value].reverse())

async function loadData(chatId = '', supportChatId = '') {
  loading.value = true
  error.value = ''

  try {
    const url = new URL('/api/admin/telegram/get_dashboard.php', window.location.origin)
    if (chatId) url.searchParams.set('chat_id', String(chatId))
    if (supportChatId) url.searchParams.set('support_chat_id', String(supportChatId))

    const res = await fetch(url.toString(), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    })
    const data = await res.json().catch(() => null)
    if (!res.ok || !data?.ok) {
      throw new Error(data?.error || 'Не удалось загрузить данные Telegram-бота')
    }

    stats.value = { ...stats.value, ...(data.stats || {}) }
    supportStats.value = { ...supportStats.value, ...(data.support_stats || {}) }
    users.value = Array.isArray(data.users) ? data.users : []
    actions.value = Array.isArray(data.actions) ? data.actions : []
    supportThreads.value = Array.isArray(data.support_threads) ? data.support_threads : []
    supportMessages.value = Array.isArray(data.support_messages) ? data.support_messages : []
    selectedChatId.value = data.selected_chat_id ? String(data.selected_chat_id) : ''
    selectedSupportChatId.value = data.selected_support_chat_id ? String(data.selected_support_chat_id) : ''
    selectedUser.value = data.selected_user || null
    selectedSupportThread.value = data.selected_support_thread || null
    supportEnabled.value = Boolean(data.support_enabled)
  } catch (err) {
    error.value = err?.message || 'Не удалось загрузить данные'
  } finally {
    loading.value = false
  }
}

function selectUser(chatId) {
  loadData(String(chatId), selectedSupportChatId.value)
}

function showGlobalActions() {
  loadData('', selectedSupportChatId.value)
}

function selectSupportThread(chatId) {
  loadData(selectedChatId.value, String(chatId))
}

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  loadData(params.get('chat_id') || '', params.get('support_chat_id') || '')
})
</script>

<style scoped>
.tg-admin-page {
  min-height: 100dvh;
  padding: clamp(16px, 3vw, 28px);
  background: var(--bg-main);
  color: var(--text-main);
}

.head,
.panel,
.statsGrid {
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

.refreshBtn,
.ghostBtn {
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  color: var(--text-main);
  border-radius: var(--radius-md);
  padding: 10px 14px;
  cursor: pointer;
}

.refreshBtn:disabled {
  opacity: 0.6;
  cursor: default;
}

.ghostBtn {
  background: transparent;
}

.statsGrid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.statCard,
.panel,
.selectedCard,
.infoTile,
.messageCard {
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  background: var(--bg-panel);
  box-shadow: var(--shadow-sm);
}

.statCard {
  padding: 14px 16px;
}

.statLabel,
.metaLabel {
  font-size: 12px;
  color: var(--text-muted);
}

.statValue,
.metaValue,
.selectedName,
.itemMain {
  color: var(--text-main);
}

.statValue {
  font-size: 28px;
  font-weight: 800;
  margin-top: 8px;
}

.statValue.ok,
.badge.ok {
  color: #0f8a39;
}

.statValue.warn,
.badge.warn,
.counterBadge {
  color: #ad6a00;
}

.panel {
  padding: 16px;
}

.panelHead {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
  margin-bottom: 14px;
}

.panelTitle {
  margin: 0;
  font-size: 20px;
}

.searchInput {
  width: min(340px, 100%);
  border: 1px solid var(--border-soft);
  background: var(--bg-main);
  color: var(--text-main);
  border-radius: var(--radius-md);
  padding: 10px 12px;
}

.tileGrid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
}

.infoTile {
  text-align: left;
  padding: 14px;
  cursor: pointer;
  transition: transform .14s ease, border-color .14s ease, box-shadow .14s ease;
}

.infoTile:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.infoTile.active {
  border-color: var(--accent);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 22%, transparent);
}

.tileTop,
.tileFoot,
.selectedMain,
.selectedMetaGrid,
.messageMeta {
  display: flex;
  gap: 12px;
}

.tileTop,
.selectedMain,
.messageMeta {
  justify-content: space-between;
  align-items: flex-start;
}

.tileMetaRow {
  margin-top: 12px;
}

.tileFoot {
  justify-content: space-between;
  align-items: flex-end;
  margin-top: 12px;
}

.tileRight {
  text-align: right;
}

.detailText,
.itemSub {
  margin-top: 4px;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.35;
}

.itemMain,
.selectedName {
  font-weight: 700;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace;
}

.badge,
.counterBadge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 700;
  background: var(--bg-main);
  border: 1px solid var(--border-soft);
  white-space: nowrap;
}

.counterBadge {
  min-width: 32px;
}

.selectedCard {
  padding: 14px;
  margin-bottom: 14px;
}

.selectedMetaGrid {
  margin-top: 12px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
}

.strong {
  font-weight: 700;
}

.tableWrap {
  overflow: auto;
}

.dataTable {
  width: 100%;
  border-collapse: collapse;
}

.dataTable th,
.dataTable td {
  padding: 12px 10px;
  border-bottom: 1px solid var(--border-soft);
  vertical-align: top;
  text-align: left;
}

.dataTable th {
  font-size: 12px;
  color: var(--text-muted);
  font-weight: 700;
  position: sticky;
  top: 0;
  background: var(--bg-panel);
}

.supportThreadWrap {
  display: grid;
  gap: 12px;
}

.messageCard {
  padding: 14px;
}

.messageText {
  margin-top: 8px;
  white-space: pre-wrap;
  line-height: 1.55;
}

.emptyBox,
.errorBox {
  border: 1px dashed var(--border-soft);
  border-radius: var(--radius-lg);
  padding: 18px;
  color: var(--text-muted);
  background: color-mix(in srgb, var(--bg-panel) 85%, transparent);
}

.errorBox {
  color: #b23c17;
  border-style: solid;
}

@media (max-width: 760px) {
  .head,
  .panelHead,
  .selectedMain {
    flex-direction: column;
  }

  .searchInput {
    width: 100%;
  }

  .tileFoot {
    flex-direction: column;
    align-items: flex-start;
  }

  .tileRight {
    text-align: left;
  }
}
</style>

<template>
  <div class="tg-admin-page">
    <header class="head">
      <div>
        <h1 class="title">Telegram-бот</h1>
        <p class="subtitle">Управление пользователями первого бота и чатами техподдержки</p>
      </div>
      <button class="refreshBtn" :disabled="loading || supportSending" @click="loadData(selectedChatId, selectedSupportThreadId)">
        {{ loading ? 'Обновление...' : 'Обновить' }}
      </button>
    </header>

    <div class="statusStrip">
      <div class="statusPill">
        <span class="statusName">Пользователи</span>
        <strong>{{ stats.total_users }}</strong>
      </div>
      <div class="statusPill ok">
        <span class="statusName">Приняли политику</span>
        <strong>{{ stats.accepted_users }}</strong>
      </div>
      <div class="statusPill warn">
        <span class="statusName">Новые сообщения</span>
        <strong>{{ supportStats.unread_messages }}</strong>
      </div>
      <div class="statusPill warn">
        <span class="statusName">Ждут ответа</span>
        <strong>{{ supportStats.waiting_replies }}</strong>
      </div>
    </div>

    <nav class="sectionTabs" aria-label="Разделы Telegram-бота">
      <button type="button" class="tabBtn" :class="{ active: activeTab === 'summary' }" @click="setActiveTab('summary')">
        Сводка
      </button>
      <button type="button" class="tabBtn" :class="{ active: activeTab === 'users' }" @click="setActiveTab('users')">
        Пользователи
        <span class="tabBadge">{{ stats.total_users }}</span>
      </button>
      <button type="button" class="tabBtn" :class="{ active: activeTab === 'support' }" @click="setActiveTab('support')">
        Техподдержка
        <span class="tabBadge warn">{{ supportStats.unread_messages }}</span>
      </button>
    </nav>

    <div v-if="error" class="errorBox globalError">{{ error }}</div>

    <section v-if="activeTab === 'summary'" class="panel summaryPanel">
      <div class="panelHead compactHead">
        <div>
          <h2 class="panelTitle">Сводка</h2>
          <p class="panelSub">Быстрые показатели и переходы в нужный раздел</p>
        </div>
      </div>

      <div class="summaryGrid">
        <article class="summaryCard accent">
          <div class="summaryTitle">Техподдержка</div>
          <div class="summaryValue">{{ supportStats.total_threads }}</div>
          <div class="summaryText">Всего диалогов в системе</div>
          <button type="button" class="summaryAction primary" @click="setActiveTab('support')">
            Открыть чаты
          </button>
        </article>

        <article class="summaryCard">
          <div class="summaryTitle">Активные чаты</div>
          <div class="summaryValue">{{ supportStats.active_threads }}</div>
          <div class="summaryText">Открытые диалоги, в которые можно отвечать</div>
        </article>

        <article class="summaryCard">
          <div class="summaryTitle">Закрытые чаты</div>
          <div class="summaryValue">{{ supportStats.closed_threads }}</div>
          <div class="summaryText">Закрытые обращения, которые можно открыть снова</div>
        </article>

        <article class="summaryCard">
          <div class="summaryTitle">Архив</div>
          <div class="summaryValue">{{ supportStats.archived_threads }}</div>
          <div class="summaryText">Архивные диалоги магазина</div>
        </article>
      </div>
    </section>

    <section v-if="activeTab === 'users'" class="panel workspacePanel">
      <div class="workspace usersWorkspace">
        <aside class="sidebar usersSidebar">
          <div class="sidebarHead">
            <div>
              <h2 class="panelTitle">Пользователи</h2>
              <p class="panelSub">Выберите пользователя, чтобы посмотреть его историю</p>
            </div>
            <input v-model.trim="filterText" class="searchInput" type="text" placeholder="Поиск по username, имени, chat_id" />
          </div>

          <div v-if="loading && !users.length" class="emptyBox sidebarEmpty">Загрузка данных...</div>
          <div v-else-if="!filteredUsers.length" class="emptyBox sidebarEmpty">Пользователи пока не найдены.</div>
          <div v-else class="sidebarList">
            <button
              v-for="user in filteredUsers"
              :key="`user-${user.chat_id}`"
              type="button"
              class="listCard"
              :class="{ active: String(user.chat_id) === selectedChatId }"
              @click="selectUser(user.chat_id)"
            >
              <div class="listCardTop">
                <div>
                  <div class="itemMain">{{ formatUserName(user) }}</div>
                  <div v-if="user.username" class="itemSub">@{{ user.username }}</div>
                </div>
                <span class="badge" :class="user.consent_accepted ? 'ok' : 'warn'">
                  {{ user.consent_accepted ? 'Принято' : 'Не принято' }}
                </span>
              </div>

              <div class="itemSub mono">{{ user.chat_id }}</div>
              <div class="compactRow">
                <span class="metaLabel">Последнее действие</span>
                <span class="metaValue">{{ user.last_action_label || 'Нет действий' }}</span>
              </div>
              <div v-if="user.details_text" class="previewText">{{ user.details_text }}</div>
            </button>
          </div>
        </aside>

        <div class="detailsPane">
          <template v-if="selectedUser">
            <div class="detailHeaderCard">
              <div>
                <div class="selectedName">{{ formatUserName(selectedUser) }}</div>
                <div v-if="selectedUser.username" class="itemSub">@{{ selectedUser.username }}</div>
                <div class="itemSub mono">Chat ID: {{ selectedUser.chat_id }}</div>
              </div>
              <span class="badge" :class="selectedUser.consent_accepted ? 'ok' : 'warn'">
                {{ selectedUser.consent_accepted ? 'Политика принята' : 'Политика не принята' }}
              </span>
            </div>

            <div class="miniStats">
              <div class="miniStat">
                <span class="metaLabel">Дата согласия</span>
                <strong>{{ formatDate(selectedUser.consent_at) }}</strong>
              </div>
              <div class="miniStat">
                <span class="metaLabel">Всего действий</span>
                <strong>{{ selectedUser.total_actions }}</strong>
              </div>
              <div class="miniStat">
                <span class="metaLabel">Последняя активность</span>
                <strong>{{ formatDate(selectedUser.updated_at) }}</strong>
              </div>
            </div>

            <div class="detailSectionHead">
              <h3 class="detailSectionTitle">История действий</h3>
            </div>

            <div v-if="!actions.length" class="emptyBox">История действий пока пуста.</div>
            <div v-else class="activityList">
              <article v-for="action in actions" :key="action.id" class="activityCard">
                <div class="activityTop">
                  <div>
                    <div class="itemMain">{{ action.action_label }}</div>
                    <div class="itemSub mono">{{ action.action_code }}</div>
                  </div>
                  <div class="metaValue">{{ formatDate(action.created_at) }}</div>
                </div>
                <div class="previewText">{{ action.details_text || 'Без дополнительных данных' }}</div>
              </article>
            </div>
          </template>
          <div v-else class="emptyBox detailsEmpty">Выберите пользователя слева, чтобы открыть историю.</div>
        </div>
      </div>
    </section>

    <section v-if="activeTab === 'support'" class="panel workspacePanel">
      <div class="workspace supportWorkspace">
        <aside class="sidebar supportSidebar">
          <div class="sidebarHead">
            <div>
              <h2 class="panelTitle">Техподдержка</h2>
              <p class="panelSub">Активные, закрытые и архивные диалоги</p>
            </div>
            <input v-model.trim="supportFilterText" class="searchInput" type="text" placeholder="Поиск по username, имени, chat_id" />
          </div>

          <div class="supportModeTabs">
            <button type="button" class="modeBtn" :class="{ active: supportMode === 'active' }" @click="supportMode = 'active'">
              Активные
              <span class="tabBadge">{{ supportStats.active_threads }}</span>
            </button>
            <button type="button" class="modeBtn" :class="{ active: supportMode === 'closed' }" @click="supportMode = 'closed'">
              Закрытые
              <span class="tabBadge">{{ supportStats.closed_threads }}</span>
            </button>
            <button type="button" class="modeBtn" :class="{ active: supportMode === 'archived' }" @click="supportMode = 'archived'">
              Архив
              <span class="tabBadge">{{ supportStats.archived_threads }}</span>
            </button>
          </div>

          <div v-if="!supportEnabled" class="emptyBox sidebarEmpty">Файлы второго бота техподдержки ещё не подключены.</div>
          <div v-else-if="!filteredSupportThreads.length" class="emptyBox sidebarEmpty">В этом разделе чатов пока нет.</div>
          <div v-else class="sidebarList">
            <button
              v-for="thread in filteredSupportThreads"
              :key="`support-${thread.id}`"
              type="button"
              class="listCard supportCard"
              :class="{ active: Number(thread.id) === selectedSupportThreadId }"
              @click="selectSupportThread(thread.id)"
            >
              <div class="listCardTop">
                <div>
                  <div class="itemMain">{{ formatUserName(thread) }}</div>
                  <div v-if="thread.username" class="itemSub">@{{ thread.username }}</div>
                </div>
                <span class="threadTime">{{ formatDateShort(thread.last_user_message_at || thread.last_message_at || thread.updated_at) }}</span>
              </div>

              <div class="itemSub mono">Chat ID: {{ thread.chat_id }} - Диалог #{{ thread.id }}</div>
              <div class="previewText oneLine">{{ thread.last_message_preview || 'Сообщений пока нет' }}</div>
              <div class="threadBadges">
                <span v-if="thread.unread_count > 0" class="counterBadge">{{ thread.unread_count }}</span>
                <span class="badge" :class="threadStatusClass(thread)">
                  {{ threadStatusLabel(thread) }}
                </span>
              </div>
            </button>
          </div>
        </aside>

        <div class="chatPane">
          <template v-if="selectedSupportThread">
            <div class="chatHeader">
              <div>
                <div class="selectedName">{{ formatUserName(selectedSupportThread) }}</div>
                <div v-if="selectedSupportThread.username" class="itemSub">@{{ selectedSupportThread.username }}</div>
                <div class="itemSub mono">Chat ID: {{ selectedSupportThread.chat_id }} - Диалог #{{ selectedSupportThread.id }}</div>
              </div>
              <div class="chatHeaderMeta">
                <span v-if="selectedSupportThread.unread_count > 0" class="counterBadge">{{ selectedSupportThread.unread_count }}</span>
                <span class="badge" :class="threadStatusClass(selectedSupportThread)">
                  {{ threadStatusLabel(selectedSupportThread) }}
                </span>
              </div>
            </div>

            <div class="chatHeaderActions">
              <button v-if="selectedSupportThread.status === 'active'" type="button" class="summaryAction" @click="updateSupportThread('close')">
                Закрыть чат
              </button>
              <button v-if="selectedSupportThread.status !== 'archived'" type="button" class="summaryAction" @click="updateSupportThread('archive')">
                В архив
              </button>
              <button v-if="selectedSupportThread.status !== 'active'" type="button" class="summaryAction" @click="updateSupportThread('reopen')">
                Открыть снова
              </button>
              <button type="button" class="summaryAction danger" @click="updateSupportThread('delete')">
                Удалить чат
              </button>
            </div>

            <div ref="chatMessagesRef" class="chatMessages">
              <div v-if="!supportMessages.length" class="emptyBox">История сообщений пока пуста.</div>
              <article
                v-for="message in supportMessages"
                :key="message.id"
                class="bubbleWrap"
                :class="[message.direction === 'outgoing' ? 'outgoing' : 'incoming', { deleted: isMessageDeleted(message) }]"
              >
                <div class="messageBubble">
                  <div class="bubbleHead">
                    <span class="bubbleAuthor">{{ message.direction === 'outgoing' ? 'Магазин' : formatUserName(selectedSupportThread) }}</span>
                    <div class="bubbleHeadRight">
                      <span class="bubbleStatus" :class="supportMessageStatusClass(message)">
                        {{ supportMessageStatus(message) }}
                      </span>
                      <button
                        v-if="canEditMessage(message)"
                        type="button"
                        class="iconActionBtn"
                        title="Редактировать сообщение"
                        @click="startEditMessage(message)"
                      >
                        ✎
                      </button>
                      <button
                        v-if="canDeleteMessage(message)"
                        type="button"
                        class="iconActionBtn"
                        title="Удалить сообщение у всех"
                        @click="deleteSupportMessage(message)"
                      >
                        🗑
                      </button>
                    </div>
                  </div>
                  <div v-if="isMessageDeleted(message)" class="messageDeleted">Сообщение удалено</div>
                  <template v-else>
                    <div v-if="message.content_type === 'photo' && message.media_url" class="messageMedia">
                      <img :src="message.media_url" alt="Фотография" loading="lazy" />
                    </div>
                    <div v-if="formatMessageText(message)" class="messageText">{{ formatMessageText(message) }}</div>
                    <div v-if="message.edited_at" class="messageEdited">Изменено</div>
                  </template>
                  <div class="bubbleTime">{{ formatDate(message.created_at) }}</div>
                </div>
              </article>
            </div>

            <div v-if="editingMessageId" class="editNotice">
              <div>
                <strong>Редактирование сообщения</strong>
                <div class="itemSub">Изменения применятся к сообщению магазина в Telegram.</div>
              </div>
              <button type="button" class="summaryAction" @click="cancelEditMessage">Отменить</button>
            </div>

            <form class="replyForm" @submit.prevent="sendSupportReply">
              <textarea
                v-model="supportReplyText"
                class="replyTextarea"
                :placeholder="editingMessageId ? 'Измените текст сообщения магазина' : 'Введите ответ пользователю'"
                rows="5"
                :disabled="selectedSupportThread.status !== 'active' && !editingMessageId"
                @keydown="handleSupportTextareaKeydown"
              />
              <div v-if="supportPhotoPreview" class="replyPreviewBox">
                <img :src="supportPhotoPreview" alt="Предпросмотр" class="replyPreviewImage" />
                <button type="button" class="iconActionBtn" @click="clearSupportAttachment">✕</button>
              </div>
              <div class="replyToolbar" v-if="!editingMessageId">
                <label class="attachBtn" :class="{ disabled: selectedSupportThread.status !== 'active' }">
                  <input type="file" accept="image/*" class="hiddenFileInput" :disabled="selectedSupportThread.status !== 'active'" @change="onSupportPhotoChange" />
                  📎 Фото
                </label>
              </div>
              <div class="replyActions">
                <p class="replyHint">
                  Enter - новая строка. Shift + Enter - {{ editingMessageId ? 'сохранить изменения' : 'отправить' }}.
                </p>
                <div class="replyButtons">
                  <button
                    class="sendBtn"
                    type="submit"
                    :disabled="sendDisabled"
                  >
                    {{ supportSending ? 'Отправка...' : editingMessageId ? 'Сохранить изменения' : 'Отправить ответ' }}
                  </button>
                  <button v-if="editingMessageId" type="button" class="summaryAction" @click="cancelEditMessage">Отменить</button>
                </div>
              </div>
            </form>
          </template>
          <div v-else class="emptyBox chatEmpty">Выберите чат слева, чтобы открыть переписку.</div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useHead } from '@vueuse/head'

useHead({
  title: 'Telegram-бот - админ-панель',
  meta: [{ name: 'robots', content: 'noindex,nofollow' }],
})

const loading = ref(false)
const supportSending = ref(false)
const error = ref('')
const filterText = ref('')
const supportFilterText = ref('')
const supportMode = ref('active')
const supportReplyText = ref('')
const supportPhotoFile = ref(null)
const supportPhotoPreview = ref('')
const editingMessageId = ref(0)
const chatMessagesRef = ref(null)
const selectedChatId = ref('')
const selectedSupportThreadId = ref(0)
const selectedUser = ref(null)
const selectedSupportThread = ref(null)
const supportEnabled = ref(false)
const activeTab = ref('support')

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
  waiting_replies: 0,
  active_threads: 0,
  closed_threads: 0,
  archived_threads: 0,
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

function formatDateShort(value) {
  if (!value) return ''
  const date = new Date(String(value).replace(' ', 'T'))
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('ru-RU', {
    day: '2-digit',
    month: '2-digit',
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

function threadStatusLabel(thread) {
  if (thread?.status === 'archived') return 'В архиве'
  if (thread?.status === 'closed') return 'Закрыт'
  return thread?.needs_reply ? 'Ждёт ответа' : 'Активен'
}

function threadStatusClass(thread) {
  if (thread?.status === 'archived') return 'neutral'
  if (thread?.status === 'closed') return 'warn'
  return thread?.needs_reply ? 'warn' : 'ok'
}

function supportMessageStatus(message) {
  if (message?.direction === 'outgoing') return 'Отправлено'
  return Number(message?.is_read) ? 'Прочитано' : 'Непрочитано'
}

function supportMessageStatusClass(message) {
  if (message?.direction === 'outgoing') return 'statusOutgoing'
  return Number(message?.is_read) ? 'statusRead' : 'statusUnread'
}

function isMessageDeleted(message) {
  return Boolean(message?.deleted_at)
}

function formatMessageText(message) {
  if (isMessageDeleted(message)) return ''
  const text = String(message?.message_text || '').trim()
  if (message?.content_type === 'photo' && text === 'Фотография') return ''
  return text
}

function canDeleteMessage(message) {
  return !isMessageDeleted(message) && Number(message?.telegram_message_id || 0) > 0
}

function canEditMessage(message) {
  return !isMessageDeleted(message) && message?.direction === 'outgoing' && Number(message?.telegram_message_id || 0) > 0
}

function scheduleChatScroll() {
  nextTick(() => {
    const box = chatMessagesRef.value
    if (!box) return
    box.scrollTop = box.scrollHeight
  })
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

  return supportThreads.value.filter((thread) => {
    if ((thread.status || 'active') !== supportMode.value) return false
    if (!q) return true

    const haystack = [
      thread.username,
      thread.first_name,
      thread.last_name,
      thread.chat_id,
      thread.user_id,
      thread.last_message_preview,
      thread.id,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    return haystack.includes(q)
  })
})

const sendDisabled = computed(() => {
  if (!selectedSupportThread.value) return true
  if (supportSending.value) return true
  if (editingMessageId.value) return !supportReplyText.value.trim()
  if (selectedSupportThread.value.status !== 'active') return true
  return !supportReplyText.value.trim() && !supportPhotoFile.value
})

function syncQueryParams() {
  const url = new URL(window.location.href)

  if (activeTab.value) url.searchParams.set('tab', activeTab.value)
  else url.searchParams.delete('tab')

  if (selectedChatId.value) url.searchParams.set('chat_id', selectedChatId.value)
  else url.searchParams.delete('chat_id')

  if (selectedSupportThreadId.value) url.searchParams.set('support_thread_id', String(selectedSupportThreadId.value))
  else url.searchParams.delete('support_thread_id')

  window.history.replaceState({}, '', `${url.pathname}${url.search}`)
}

function setActiveTab(tab) {
  activeTab.value = tab
  syncQueryParams()
}

async function loadData(chatId = '', supportThreadId = 0, options = {}) {
  const silent = Boolean(options?.silent)
  const prevLastId = supportMessages.value.length ? supportMessages.value[supportMessages.value.length - 1].id : 0
  if (!silent) loading.value = true
  if (!silent) error.value = ''

  try {
    const url = new URL('/api/admin/telegram/get_dashboard.php', window.location.origin)
    if (chatId) url.searchParams.set('chat_id', String(chatId))
    if (supportThreadId) url.searchParams.set('support_thread_id', String(supportThreadId))

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
    selectedSupportThreadId.value = Number(data.selected_support_thread_id || 0)
    selectedUser.value = data.selected_user || null
    selectedSupportThread.value = data.selected_support_thread || null
    supportEnabled.value = Boolean(data.support_enabled)

    if (activeTab.value === 'support' && !supportEnabled.value) {
      activeTab.value = 'users'
    }

    if (selectedSupportThread.value?.status && supportMode.value !== selectedSupportThread.value.status) {
      supportMode.value = selectedSupportThread.value.status
    }

    syncQueryParams()

    const nextLastId = supportMessages.value.length ? supportMessages.value[supportMessages.value.length - 1].id : 0
    if (selectedSupportThreadId.value && (nextLastId !== prevLastId || !silent)) {
      scheduleChatScroll()
    }
  } catch (err) {
    error.value = err?.message || 'Не удалось загрузить данные'
  } finally {
    if (!silent) loading.value = false
  }
}

function selectUser(chatId) {
  activeTab.value = 'users'
  loadData(String(chatId), selectedSupportThreadId.value)
}

function selectSupportThread(threadId) {
  activeTab.value = 'support'
  cancelEditMessage()
  loadData(selectedChatId.value, Number(threadId))
}

async function sendSupportReply() {
  if (!selectedSupportThread.value || sendDisabled.value) return

  supportSending.value = true
  error.value = ''
  try {
    if (editingMessageId.value) {
      const res = await fetch('/api/admin/telegram/update_support_message.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          message_id: Number(editingMessageId.value),
          message_text: supportReplyText.value.trim(),
        }),
      })
      const data = await res.json().catch(() => null)
      if (!res.ok || !data?.ok) {
        throw new Error(data?.error || 'Не удалось изменить сообщение')
      }
      cancelEditMessage()
    } else {
      const body = new FormData()
      body.append('conversation_id', String(selectedSupportThread.value.id))
      body.append('message_text', supportReplyText.value.trim())
      if (supportPhotoFile.value) body.append('photo', supportPhotoFile.value)

      const res = await fetch('/api/admin/telegram/send_support_reply.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
        body,
      })
      const data = await res.json().catch(() => null)
      if (!res.ok || !data?.ok) {
        throw new Error(data?.error || 'Не удалось отправить ответ')
      }
      supportReplyText.value = ''
      clearSupportAttachment()
    }

    await loadData(selectedChatId.value, selectedSupportThreadId.value)
  } catch (err) {
    error.value = err?.message || 'Не удалось отправить ответ'
  } finally {
    supportSending.value = false
  }
}

function handleSupportTextareaKeydown(event) {
  if (event.key === 'Enter' && event.shiftKey) {
    event.preventDefault()
    if (!supportSending.value) sendSupportReply()
  }
}

function onSupportPhotoChange(event) {
  const input = event.target
  const file = input?.files?.[0] || null
  if (!file) return
  supportPhotoFile.value = file
  supportPhotoPreview.value = URL.createObjectURL(file)
}

function clearSupportAttachment() {
  if (supportPhotoPreview.value) URL.revokeObjectURL(supportPhotoPreview.value)
  supportPhotoPreview.value = ''
  supportPhotoFile.value = null
}

function startEditMessage(message) {
  if (!canEditMessage(message)) return
  editingMessageId.value = Number(message.id)
  supportReplyText.value = formatMessageText(message)
  clearSupportAttachment()
  scheduleChatScroll()
}

function cancelEditMessage() {
  editingMessageId.value = 0
  supportReplyText.value = ''
  clearSupportAttachment()
}

async function deleteSupportMessage(message) {
  if (!message?.id) return
  if (!window.confirm('Удалить сообщение у всех?')) return

  error.value = ''
  try {
    const res = await fetch('/api/admin/telegram/delete_support_message.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({ message_id: Number(message.id) }),
    })
    const data = await res.json().catch(() => null)
    if (!res.ok || !data?.ok) {
      throw new Error(data?.error || 'Не удалось удалить сообщение')
    }
    if (editingMessageId.value === Number(message.id)) {
      cancelEditMessage()
    }
    await loadData(selectedChatId.value, selectedSupportThreadId.value)
  } catch (err) {
    error.value = err?.message || 'Не удалось удалить сообщение'
  }
}

async function updateSupportThread(action) {
  if (!selectedSupportThread.value) return

  const labels = {
    close: 'Закрыть чат? Следующее сообщение пользователя откроет новый диалог.',
    archive: 'Переместить чат в архив?',
    reopen: 'Открыть чат снова?',
    delete: 'Удалить весь чат? Это скроет его из админки и попытается удалить сообщения у пользователя там, где Telegram это позволяет.',
  }
  if (labels[action] && !window.confirm(labels[action])) return

  error.value = ''
  try {
    const res = await fetch('/api/admin/telegram/update_support_thread.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        conversation_id: Number(selectedSupportThread.value.id),
        action,
      }),
    })
    const data = await res.json().catch(() => null)
    if (!res.ok || !data?.ok) {
      throw new Error(data?.error || 'Не удалось обновить чат')
    }

    if (action === 'delete') {
      selectedSupportThreadId.value = 0
      selectedSupportThread.value = null
      supportMessages.value = []
      cancelEditMessage()
      await loadData(selectedChatId.value, 0)
      return
    }

    await loadData(selectedChatId.value, selectedSupportThreadId.value)
  } catch (err) {
    error.value = err?.message || 'Не удалось обновить чат'
  }
}

let supportPollTimer = null

function startSupportPolling() {
  stopSupportPolling()
  supportPollTimer = window.setInterval(() => {
    if (document.hidden) return
    loadData(selectedChatId.value, selectedSupportThreadId.value, { silent: true })
  }, 4000)
}

function stopSupportPolling() {
  if (supportPollTimer) {
    window.clearInterval(supportPollTimer)
    supportPollTimer = null
  }
}

function handleVisibilityChange() {
  if (!document.hidden) {
    loadData(selectedChatId.value, selectedSupportThreadId.value, { silent: true })
  }
}

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  const tab = params.get('tab')
  const supportThreadId = Number(params.get('support_thread_id') || 0)
  const userChatId = params.get('chat_id') || ''

  if (tab === 'summary' || tab === 'users' || tab === 'support') {
    activeTab.value = tab
  } else if (supportThreadId) {
    activeTab.value = 'support'
  } else if (userChatId) {
    activeTab.value = 'users'
  } else {
    activeTab.value = 'support'
  }

  loadData(userChatId, supportThreadId)
  startSupportPolling()
  document.addEventListener('visibilitychange', handleVisibilityChange)
})

onBeforeUnmount(() => {
  stopSupportPolling()
  document.removeEventListener('visibilitychange', handleVisibilityChange)
  clearSupportAttachment()
})
</script>

<style scoped>
.tg-admin-page {
  min-height: 100dvh;
  padding: clamp(12px, 2.8vw, 24px);
  background: var(--bg-main);
  color: var(--text-main);
}

.head,
.statusStrip,
.sectionTabs,
.panel,
.globalError {
  max-width: 1320px;
  margin: 0 auto 14px;
}

.head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
}

.title {
  margin: 0;
  font-size: clamp(24px, 2.1vw, 32px);
}

.subtitle,
.panelSub,
.replyHint,
.summaryText,
.previewText,
.itemSub,
.threadTime,
.bubbleTime,
.bubbleStatus,
.metaLabel,
.statusName,
.messageEdited {
  color: var(--text-muted);
}

.subtitle,
.panelSub,
.replyHint,
.summaryText,
.previewText,
.itemSub {
  line-height: 1.45;
}

.refreshBtn,
.sendBtn,
.summaryAction,
.tabBtn,
.modeBtn {
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  color: var(--text-main);
  border-radius: var(--radius-md);
  padding: 10px 14px;
  cursor: pointer;
}

.refreshBtn:disabled,
.sendBtn:disabled {
  opacity: 0.65;
  cursor: default;
}

.sendBtn,
.summaryAction.primary,
.tabBtn.active,
.modeBtn.active {
  background: var(--accent);
  color: #fff;
  border-color: transparent;
}

.summaryAction.danger {
  border-color: rgba(185, 38, 38, 0.35);
  color: #b92626;
}

.statusStrip {
  display: grid;
  gap: 10px;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
}

.statusPill {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding: 11px 12px;
  border-radius: 14px;
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  box-shadow: var(--shadow-sm);
}

.statusPill strong {
  font-size: 18px;
  color: var(--text-main);
}

.statusPill.ok strong,
.badge.ok,
.summaryValue.ok,
.bubbleStatus.statusRead,
.bubbleStatus.statusOutgoing {
  color: #0f8a39;
}

.statusPill.warn strong,
.tabBadge.warn,
.counterBadge,
.badge.warn,
.summaryValue.warn,
.bubbleStatus.statusUnread,
.badge.neutral {
  color: #ad6a00;
}

.sectionTabs {
  position: sticky;
  top: 0;
  z-index: 5;
  display: flex;
  gap: 8px;
  padding: 8px;
  border-radius: 16px;
  background: color-mix(in srgb, var(--bg-main) 88%, transparent);
  backdrop-filter: blur(10px);
  border: 1px solid var(--border-soft);
}

.tabBtn,
.modeBtn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 42px;
  font-weight: 700;
}

.tabBadge,
.badge,
.counterBadge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  padding: 5px 9px;
  border-radius: 999px;
  border: 1px solid var(--border-soft);
  background: var(--bg-main);
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.panel {
  padding: 14px;
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  background: var(--bg-panel);
  box-shadow: var(--shadow-sm);
}

.compactHead {
  margin-bottom: 10px;
}

.panelTitle,
.detailSectionTitle {
  margin: 0;
  font-size: 20px;
}

.summaryGrid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
}

.summaryCard {
  display: grid;
  gap: 8px;
  padding: 16px;
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  background: var(--bg-soft);
}

.summaryCard.accent {
  background: color-mix(in srgb, var(--accent) 7%, var(--bg-panel));
  border-color: color-mix(in srgb, var(--accent) 26%, var(--border-soft));
}

.summaryTitle {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-muted);
}

.summaryValue {
  font-size: 32px;
  font-weight: 800;
  color: var(--text-main);
}

.summaryAction {
  justify-self: start;
}

.workspace {
  display: grid;
  gap: 14px;
}

.usersWorkspace,
.supportWorkspace {
  grid-template-columns: minmax(300px, 360px) minmax(0, 1fr);
}

.sidebar,
.detailsPane,
.chatPane {
  min-height: 620px;
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  background: var(--bg-soft);
}

.sidebar {
  display: flex;
  flex-direction: column;
}

.sidebarHead {
  display: grid;
  gap: 10px;
  padding: 14px;
  border-bottom: 1px solid var(--border-soft);
}

.searchInput {
  width: 100%;
  min-height: 42px;
  border-radius: 12px;
  border: 1px solid var(--border-soft);
  padding: 10px 12px;
  background: var(--bg-panel);
  color: var(--text-main);
}

.supportModeTabs {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
  padding: 12px 14px 0;
}

.sidebarList {
  display: grid;
  gap: 10px;
  padding: 14px;
  overflow: auto;
}

.listCard {
  width: 100%;
  text-align: left;
  display: grid;
  gap: 8px;
  padding: 12px;
  border-radius: 16px;
  border: 1px solid var(--border-soft);
  background: var(--bg-panel);
  cursor: pointer;
}

.listCard.active {
  border-color: color-mix(in srgb, var(--accent) 45%, var(--border-soft));
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent) 24%, transparent);
}

.listCardTop,
.activityTop,
.compactRow,
.chatHeader,
.chatHeaderActions,
.bubbleHead,
.bubbleHeadRight,
.replyActions,
.detailHeaderCard,
.threadBadges,
.threadMeta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.itemMain,
.selectedName {
  font-weight: 700;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}

.previewText.oneLine {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.detailsPane,
.chatPane {
  display: flex;
  flex-direction: column;
  padding: 14px;
  gap: 12px;
}

.detailHeaderCard,
.miniStats,
.activityList,
.chatMessages,
.replyForm,
.editNotice {
  border: 1px solid var(--border-soft);
  border-radius: var(--radius-lg);
  background: var(--bg-panel);
}

.detailHeaderCard,
.editNotice {
  padding: 14px;
}

.miniStats {
  display: grid;
  gap: 10px;
  padding: 12px;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
}

.miniStat,
.activityCard {
  display: grid;
  gap: 6px;
}

.activityList {
  display: grid;
  gap: 10px;
  padding: 12px;
  overflow: auto;
}

.activityCard {
  padding: 12px;
  border-radius: 14px;
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
}

.chatMessages {
  flex: 1;
  min-height: 320px;
  padding: 14px;
  overflow: auto;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.bubbleWrap {
  display: flex;
}

.bubbleWrap.incoming {
  justify-content: flex-start;
}

.bubbleWrap.outgoing {
  justify-content: flex-end;
}

.messageBubble {
  max-width: min(720px, 92%);
  display: grid;
  gap: 8px;
  padding: 12px;
  border-radius: 16px;
  border: 1px solid var(--border-soft);
  background: var(--bg-soft);
}

.bubbleWrap.outgoing .messageBubble {
  background: color-mix(in srgb, var(--accent) 10%, var(--bg-panel));
}

.messageText {
  white-space: pre-wrap;
  line-height: 1.5;
}

.messageMedia img,
.replyPreviewImage {
  max-width: min(360px, 100%);
  border-radius: 14px;
  display: block;
}

.messageDeleted,
.messageEdited {
  font-size: 12px;
}

.replyForm {
  display: grid;
  gap: 10px;
  padding: 12px;
}

.replyTextarea {
  width: 100%;
  min-height: 132px;
  resize: vertical;
  border-radius: 14px;
  border: 1px solid var(--border-soft);
  padding: 12px;
  background: var(--bg-main);
  color: var(--text-main);
}

.replyToolbar,
.replyButtons {
  display: flex;
  gap: 10px;
  align-items: center;
}

.attachBtn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid var(--border-soft);
  cursor: pointer;
}

.attachBtn.disabled {
  opacity: 0.55;
  cursor: default;
}

.hiddenFileInput {
  display: none;
}

.replyPreviewBox {
  display: inline-flex;
  align-items: flex-start;
  gap: 8px;
}

.iconActionBtn {
  min-width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px solid var(--border-soft);
  background: var(--bg-main);
  cursor: pointer;
}

.emptyBox {
  display: grid;
  place-items: center;
  min-height: 160px;
  text-align: center;
  color: var(--text-muted);
  padding: 18px;
}

.globalError {
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid rgba(185, 38, 38, 0.25);
  background: rgba(185, 38, 38, 0.08);
  color: #8d1e1e;
}

@media (max-width: 1024px) {
  .usersWorkspace,
  .supportWorkspace {
    grid-template-columns: 1fr;
  }

  .sidebar,
  .detailsPane,
  .chatPane {
    min-height: 0;
  }
}

@media (max-width: 640px) {
  .head {
    flex-direction: column;
  }

  .sectionTabs,
  .supportModeTabs {
    overflow: auto;
  }

  .chatHeader,
  .chatHeaderActions,
  .replyActions,
  .detailHeaderCard,
  .listCardTop,
  .compactRow {
    flex-direction: column;
    align-items: flex-start;
  }

  .messageBubble {
    max-width: 100%;
  }
}
</style>

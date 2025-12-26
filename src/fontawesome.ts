// src/fontawesome.ts
import { library } from '@fortawesome/fontawesome-svg-core'

// импортируй ТОЛЬКО те иконки, которые реально используешь
import {
  faMagnifyingGlass,
  faXmark,
  faBarsStaggered,
} from '@fortawesome/free-solid-svg-icons'

library.add(
  faMagnifyingGlass,
  faXmark,
  faBarsStaggered
)

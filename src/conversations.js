import './sass/admin/pages/conversations.sass'
import {
  createIcons,
  ArrowLeft,
  Search,
  Users,
  TrendingDown,
  TrendingUp,
  ExternalLink,
  Eye,
  Download,
  Trash,
} from 'lucide'

document.addEventListener('DOMContentLoaded', () => {
  createIcons({
    icons: {
      ArrowLeft,
      Search,
      Users,
      TrendingDown,
      TrendingUp,
      ExternalLink,
      Eye,
      Download,
      Trash,
    },
  })
})

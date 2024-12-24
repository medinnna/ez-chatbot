import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './sass/index.sass'
import App from './App.jsx'

createRoot(document.getElementById('chatbot-wrapper')).render(
  <StrictMode>
    <App />
  </StrictMode>,
)

import { useState, useEffect, useRef } from 'react'
import profileImage from './img/profile.png'
import { X, Send, MessageSquare } from 'lucide-react'
import chatbotRequest from './helpers/request'

function App() {
  const chatbot_settings = {
    base_url: window.ez_chatbot_settings?.base_url ?? '',
    assets_url: window.ez_chatbot_settings?.assets_url ?? '',
    enabled: window.ez_chatbot_settings?.enabled ?? true,
    image: window.ez_chatbot_settings?.image ?? profileImage,
    name: window.ez_chatbot_settings?.name ?? 'EZ Chatbot',
    color: window.ez_chatbot_settings?.color ?? '#000',
    system: window.ez_chatbot_settings?.system ?? 'Eres un asistente virtual.',
    knowledge: window.ez_chatbot_settings?.knowledge ?? '',
    welcome:
      window.ez_chatbot_settings?.welcome ?? '¡Hola! Soy un asistente virtual.',
    placeholder:
      window.ez_chatbot_settings?.placeholder ?? '¿En qué te puedo ayudar?',
  }
  const [open, setOpen] = useState(false)
  const [messages, setMessages] = useState([
    {
      role: 'system',
      content: chatbot_settings.system + chatbot_settings.knowledge,
    },
    {
      role: 'assistant',
      content: chatbot_settings.welcome,
    },
  ])
  const [userMessage, setUserMessage] = useState('')
  const [userScroll, setUserScroll] = useState(false)
  const [loading, setLoading] = useState(false)
  const historyContainer = useRef(null)
  const sendInput = useRef(null)
  const sendButton = useRef(null)
  const previousScrollTop = useRef(0)

  const handleOpen = () => {
    setOpen((isOpen) => {
      if (!isOpen) {
        sendInput.current.focus()
      }
      return !isOpen
    })
  }

  const userMessageChange = (e) => {
    setUserMessage(e.target.value)

    if (e.target.value === '') {
      sendButton.current.disabled = true
    } else {
      sendButton.current.disabled = false
    }
  }

  const handleSubmit = async (e) => {
    if (userMessage === '') return

    setLoading(true)
    setUserScroll(false)
    sendButton.current.disabled = true

    const message = {
      role: 'user',
      content: userMessage,
    }

    const updatedMessages = [...messages, message]

    setUserMessage('')
    setMessages(updatedMessages)

    await chatbotRequest(
      loading,
      setLoading,
      updatedMessages,
      setMessages,
      chatbot_settings.base_url
    )

    e.preventDefault()
  }

  const handleScroll = () => {
    if (historyContainer.current) {
      const currentScrollTop = historyContainer.current.scrollTop

      if (currentScrollTop < previousScrollTop.current) {
        setUserScroll(true)
      } else if (
        currentScrollTop >=
        historyContainer.current.scrollHeight -
          historyContainer.current.clientHeight
      ) {
        setUserScroll(false)
      }

      previousScrollTop.current = currentScrollTop
    }
  }

  useEffect(() => {
    if (historyContainer.current && !userScroll) {
      historyContainer.current.scrollTo({
        top: historyContainer.current.scrollHeight,
        behavior: 'smooth',
      })
    }
  }, [messages, userScroll])

  if (!chatbot_settings.enabled) return null

  return (
    <>
      <div
        className={`chatbot__widget ${open ? 'is-open' : ''}`}
        style={{ '--color': chatbot_settings.color }}
      >
        <div className="chatbot__widget-window">
          <header>
            <img
              className="profile"
              src={
                chatbot_settings.image
                  ? chatbot_settings.image
                  : chatbot_settings.assets_url + profileImage
              }
            />

            <p>{chatbot_settings.name}</p>

            <X className="close" color="white" onClick={() => setOpen(false)} />
          </header>

          <main ref={historyContainer} onScroll={handleScroll}>
            <div className="messages">
              {[...messages]
                .filter((message) => message['role'] !== 'system')
                .map((message, index) => (
                  <div className="message" key={index}>
                    {message['content']}
                  </div>
                ))}
            </div>

            <div
              className="loading"
              style={{ display: loading ? 'block' : 'none' }}
            >
              <div className="dots">
                <div className="dot"></div>
                <div className="dot"></div>
                <div className="dot"></div>
              </div>
            </div>
          </main>

          <footer>
            <form action="#">
              <input
                ref={sendInput}
                type="text"
                value={userMessage}
                onChange={userMessageChange}
                placeholder={chatbot_settings.placeholder}
              />

              <button
                ref={sendButton}
                type="submit"
                onClick={handleSubmit}
                disabled={userMessage === ''}
              >
                <Send className="send" color="white" size="15" />
              </button>
            </form>
          </footer>
        </div>

        <div className="chatbot__widget-btn" onClick={() => handleOpen()}>
          <MessageSquare color="white" />
        </div>
      </div>
    </>
  )
}

export default App

import { useState, useEffect, useRef } from 'react'
import profileImage from './img/profile.png'
import { X, Send, MessageSquare } from 'lucide-react'
import chatbotRequest from './helpers/request'
import saveMessage from './helpers/saveMessage'

function App() {
  const chatbot_settings = {
    base_url: window.ez_chatbot_settings?.base_url ?? '',
    assets_url: window.ez_chatbot_settings?.assets_url ?? '',
    enabled: window.ez_chatbot_settings?.enabled ?? true,
    image: window.ez_chatbot_settings?.image ?? profileImage,
    name: window.ez_chatbot_settings?.name ?? 'EZ Chatbot',
    color: window.ez_chatbot_settings?.color ?? '#000',
    welcome:
      window.ez_chatbot_settings?.welcome ?? '¡Hola! Soy un asistente virtual.',
    placeholder:
      window.ez_chatbot_settings?.placeholder ?? '¿En qué te puedo ayudar?',
  }
  const [open, setOpen] = useState(false)
  const [messages, setMessages] = useState([
    {
      role: 'assistant',
      content: chatbot_settings.welcome,
    },
  ])
  const [userData, setUserData] = useState({
    name: null,
    email: null,
  })
  const [userMessage, setUserMessage] = useState('')
  const [loading, setLoading] = useState(false)
  const [responding, setResponding] = useState(false)
  const [buttonDisabled, setButtonDisabled] = useState(true)
  const [userScroll, setUserScroll] = useState(false)
  const historyContainer = useRef(null)
  const sendInput = useRef(null)
  const previousScrollTop = useRef(0)

  const handleOpen = () => {
    setOpen((isOpen) => {
      if (!isOpen) {
        sendInput.current.focus()
      }
      return !isOpen
    })
  }

  const handleSubmit = async (e) => {
    if (!userMessage.trim()) return

    setLoading(true)
    setResponding(true)
    setUserScroll(false)

    const message = {
      role: 'user',
      content: userMessage,
    }

    const updatedMessages = [...messages, message]

    setUserMessage('')
    setMessages(updatedMessages)

    if (userData.email) {
      saveMessage(
        message.role,
        userData.email,
        message.content,
        chatbot_settings.base_url
      )
    }

    await chatbotRequest(
      setLoading,
      setResponding,
      updatedMessages,
      setMessages,
      userData,
      setUserData,
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
    if (userMessage !== '' && !loading && !responding) {
      setButtonDisabled(false)
    } else {
      setButtonDisabled(true)
    }
  }, [userMessage, loading, responding])

  useEffect(() => {
    if (historyContainer.current && !userScroll) {
      historyContainer.current.scrollTo({
        top: historyContainer.current.scrollHeight,
        behavior: 'smooth',
      })
    }
  }, [messages, userScroll])

  return (
    <>
      {chatbot_settings.enabled && (
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
                alt="Profile image of the chatbot"
              />

              <p aria-label={`Chatbot name: ${chatbot_settings.name}`}>
                {chatbot_settings.name}
              </p>

              <X
                className="close"
                color="white"
                onClick={() => setOpen(false)}
              />
            </header>

            <main ref={historyContainer} onScroll={handleScroll}>
              <div className="messages">
                {[...messages]
                  .filter((message) => message['role'] !== 'system')
                  .map((message, index) => (
                    <div className={`message ${message['role']}`} key={index}>
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
              <form action="#" onSubmit={handleSubmit}>
                <input
                  ref={sendInput}
                  type="text"
                  value={userMessage}
                  onChange={(e) => setUserMessage(e.target.value)}
                  placeholder={chatbot_settings.placeholder}
                />

                <button type="submit" disabled={buttonDisabled}>
                  <Send className="send" color="white" size="15" />
                </button>
              </form>
            </footer>
          </div>

          <div className="chatbot__widget-btn" onClick={() => handleOpen()}>
            <MessageSquare color="white" />
          </div>
        </div>
      )}
    </>
  )
}

export default App

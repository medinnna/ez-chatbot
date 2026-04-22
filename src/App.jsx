import { useState, useEffect, useRef } from 'react'
import Markdown from 'react-markdown'
import remarkGfm from 'remark-gfm'
import rehypeExternalLinks from 'rehype-external-links'
import { X, Send, MessageSquare } from 'lucide-react'
import profileImage from './img/profile.png'
import chatbotSettings from './config/chatbotSettings'
import chatbotRequest from './helpers/request'
import saveMessage from './helpers/saveMessage'

function App() {
  const [open, setOpen] = useState(false)
  const openRef = useRef(open)
  const [messages, setMessages] = useState([
    {
      role: 'assistant',
      content: chatbotSettings.welcome,
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
  const [notification, setNotification] = useState(true)
  const historyContainer = useRef(null)
  const sendInput = useRef(null)
  const previousScrollTop = useRef(0)

  const handleOpen = () => {
    setNotification(false)

    setOpen((isOpen) => {
      if (!isOpen) {
        sendInput.current.focus()
      }
      return !isOpen
    })
  }

  const handleSubmit = async (e) => {
    e.preventDefault()

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
        chatbotSettings.base_url
      )
    }

    await chatbotRequest(
      setLoading,
      setResponding,
      updatedMessages,
      setMessages,
      userData,
      setUserData,
      chatbotSettings.base_url
    )

    setNotification(!openRef.current)
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

  useEffect(() => {
    openRef.current = open
  }, [open])

  return (
    <>
      {chatbotSettings.enabled && (
        <div
          className={`chatbot__widget ${open ? 'is-open' : ''}`}
          style={{ '--color': chatbotSettings.color }}
        >
          <div className="chatbot__widget-window">
            <header>
              <img
                className="profile"
                src={chatbotSettings.image || profileImage}
                alt="Profile image of the chatbot"
              />

              <p aria-label={`Chatbot name: ${chatbotSettings.name}`}>
                {chatbotSettings.name}
              </p>

              <X
                className="close"
                color="white"
                onClick={() => setOpen(false)}
              />
            </header>

            <main ref={historyContainer} onScroll={handleScroll}>
              <div className="messages">
                {messages
                  .filter((message) => message['role'] !== 'system')
                  .map((message, index) => (
                    <div className={`message ${message['role']}`} key={index}>
                      <Markdown
                        remarkPlugins={[remarkGfm]}
                        rehypePlugins={[
                          [
                            rehypeExternalLinks,
                            {
                              target: '_blank',
                              rel: ['noopener', 'noreferrer'],
                            },
                          ],
                        ]}
                      >
                        {message['content']}
                      </Markdown>
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
              <form onSubmit={handleSubmit}>
                <input
                  ref={sendInput}
                  type="text"
                  value={userMessage}
                  onChange={(e) => setUserMessage(e.target.value)}
                  placeholder={chatbotSettings.placeholder}
                />

                <button type="submit" disabled={buttonDisabled}>
                  <Send className="send" color="white" size="15" />
                </button>
              </form>
            </footer>
          </div>

          <div
            className={`chatbot__widget-btn ${notification && chatbotSettings.notifications ? 'notification' : ''}`}
            onClick={() => handleOpen()}
          >
            <MessageSquare color="white" />
          </div>
        </div>
      )}
    </>
  )
}

export default App

import createConversation from './createConversation'
import saveMessage from './saveMessage'

export default async function request(
  userMessage,
  setLoading,
  setResponding,
  setButtonDisabled,
  messages,
  setMessages,
  userData,
  setUserData,
  baseURL
) {
  const url = new URL(baseURL + '/wp-json/ez-chatbot/v1/openai')

  function isFunctionNeeded(functionName) {
    if (functionName === 'get_user_name' && !userData.name) {
      return true
    }

    if (functionName === 'get_user_email' && !userData.email) {
      return true
    }

    return false
  }

  function isValidEmail(lastUserMessage) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(lastUserMessage)
  }

  function getChatbotTools() {
    let functions = []

    if (isFunctionNeeded('get_user_name')) {
      functions.push(
        createToolFunction(
          'get_user_name',
          'name',
          'Guarda el nombre del usuario. Si no es un nombre válido, no llames a esta función y comunícale al usuario que no es válido.'
        )
      )
    }

    if (
      isFunctionNeeded('get_user_email') &&
      isValidEmail(messages[messages.length - 1].content)
    ) {
      functions.push(
        createToolFunction(
          'get_user_email',
          'email',
          `Guarda el correo del usuario. Usa esta función únicamente si el mensaje del usuario contiene un correo en formato válido (ejemplo: "user@example.com"). No uses esta función si el mensaje contiene otra información que no sea un correo.`
        )
      )
    }

    return functions
  }

  function createToolFunction(name, property, description) {
    return {
      type: 'function',
      function: {
        name,
        description,
        parameters: {
          type: 'object',
          properties: {
            [property]: { type: 'string', description },
          },
          required: [property],
          additionalProperties: false,
          strict: true,
        },
      },
    }
  }

  async function requestAPI() {
    const tools = getChatbotTools()
    const response = await fetch(url.toString(), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        model: 'gpt-4o-mini',
        messages,
        stream: true,
        tools: tools.length > 0 ? tools : null,
        ...(tools.length > 0 && { parallel_tool_calls: false }),
      }),
    })

    return response
  }

  try {
    const response = await requestAPI()

    if (!response.ok) {
      console.error('Error en la respuesta:', await response.text())
      return
    }

    setResponding(true)

    const reader = response.body.getReader()
    const decoder = new TextDecoder('utf-8')
    let assistantMessage = ''
    let tool = {
      name: null,
      arguments: '',
    }

    while (true) {
      const { done, value } = await reader.read()
      if (done) break

      if (value) {
        const chunk = decoder.decode(value, { stream: true })
        const chunks = chunk.split('\n').filter((line) => line.trim() !== '')

        chunks.forEach((line) => {
          if (line.startsWith('data:')) {
            const json = line.substring(5).trim()

            if (json !== '[DONE]') {
              const data = JSON.parse(json)
              const delta = data.choices[0]?.delta
              const content = delta?.content || ''

              if (delta?.tool_calls) {
                const toolFunction = delta?.tool_calls[0].function

                if (toolFunction.name) {
                  tool.name = toolFunction.name
                }

                if (toolFunction.arguments) {
                  tool.arguments += toolFunction.arguments
                }
              } else if (delta?.content) {
                setLoading(false)
                assistantMessage += content
                const updatedMessages = [...messages]

                updatedMessages[updatedMessages.length] = {
                  role: 'assistant',
                  content: assistantMessage,
                }

                setMessages(updatedMessages)
              }

              if (data.choices[0].finish_reason === 'tool_calls') {
                if (
                  tool.name === 'get_user_name' ||
                  tool.name === 'get_user_email'
                ) {
                  const args = JSON.parse(tool.arguments)
                  const functionProperty = tool.name.replace('get_user_', '')

                  setUserData((prevUserData) => {
                    const updatedData = {
                      ...prevUserData,
                      [functionProperty]: args[functionProperty],
                    }

                    request(
                      userMessage,
                      setLoading,
                      setResponding,
                      setButtonDisabled,
                      messages,
                      setMessages,
                      updatedData,
                      setUserData,
                      baseURL
                    )

                    if (tool.name === 'get_user_email') {
                      createConversation(
                        updatedData.name,
                        updatedData.email,
                        messages,
                        baseURL
                      )
                    }

                    return updatedData
                  })
                }
              } else if (data.choices[0].finish_reason === 'stop') {
                setResponding(false)

                if (userMessage) {
                  setButtonDisabled(false)
                } else {
                  setButtonDisabled(true)
                }

                if (userData.email) {
                  saveMessage(
                    'assistant',
                    userData.email,
                    assistantMessage,
                    baseURL
                  )
                }
              }
            }
          }
        })
      }
    }
  } catch (error) {
    console.error('Error on request:', error)
  }
}

import createConversation from './createConversation'
import saveMessage from './saveMessage'

export default async function request(
  setLoading,
  setResponding,
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
    let buffer = ''
    let assistantMessage = ''
    let tool = {
      name: null,
      arguments: '',
    }

    while (true) {
      const { done, value } = await reader.read()
      if (done) break

      buffer += decoder.decode(value, { stream: true })

      const events = buffer.split('\n\n')
      buffer = events.pop()

      for (const event of events) {
        if (!event.startsWith('data:')) continue

        const data = event.replace(/^data:\s*/, '').trim()

        if (data === '[DONE]') {
          setResponding(false)
          continue
        }

        let json

        try {
          json = JSON.parse(data)
        } catch {
          continue
        }

        const choice = json.choices?.[0]
        if (!choice) continue

        const delta = choice.delta

        if (delta?.content) {
          setLoading(false)

          assistantMessage += delta.content

          setMessages((prev) => {
            const updated = [...prev]
            const last = updated[updated.length - 1]

            if (!last || last.role !== 'assistant') {
              updated.push({
                role: 'assistant',
                content: assistantMessage,
              })
            } else {
              updated[updated.length - 1] = {
                ...last,
                content: assistantMessage,
              }
            }

            return updated
          })
        }

        if (delta?.tool_calls) {
          const toolCall = delta.tool_calls[0]

          if (toolCall.function?.name) {
            tool.name = toolCall.function.name
          }

          if (toolCall.function?.arguments) {
            tool.arguments += toolCall.function.arguments
          }
        }

        if (choice.finish_reason === 'tool_calls') {
          try {
            const args = JSON.parse(tool.arguments)
            const property = tool.name.replace('get_user_', '')

            const updatedData = {
              ...userData,
              [property]: args[property],
            }

            setUserData(updatedData)

            if (tool.name === 'get_user_email') {
              createConversation(updatedData, messages, baseURL)
            }

            return request(
              setLoading,
              setResponding,
              messages,
              setMessages,
              updatedData,
              setUserData,
              baseURL
            )
          } catch (err) {
            console.error('Tool parse error', err)
          }
        }

        if (choice.finish_reason === 'stop') {
          setResponding(false)

          if (userData.email) {
            saveMessage('assistant', userData.email, assistantMessage, baseURL)
          }
        }
      }
    }
  } catch (error) {
    console.error('Error on request:', error)
  }
}

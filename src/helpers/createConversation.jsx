export default async function createConversation(userData, messages, baseURL) {
  const url = new URL(baseURL + '/wp-json/ez-chatbot/v1/conversations')

  const response = await fetch(url.toString(), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      userData,
      messages,
    }),
  })

  if (!response.ok) {
    console.error('Error en la respuesta:', await response.text())
  }

  return response.ok
}

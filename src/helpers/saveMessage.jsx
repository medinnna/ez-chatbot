export default async function saveMessage(role, email, message, baseURL) {
  const url = new URL(baseURL + '/wp-json/ez-chatbot/v1/messages');

  const response = await fetch(url.toString(), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      role: role,
      email: email,
      message: message
    })
  });

  if (!response.ok) {
    console.error('Error en la respuesta:', await response.text());
  }

  return response.ok;
}
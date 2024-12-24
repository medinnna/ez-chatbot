export default async function request(messages, setMessages, baseURL) {
  const url = new URL(baseURL + '/wp-json/chatbot-widget/v1/openai');

  const response = await fetch(url.toString(), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      model: 'gpt-4o-mini',
      messages: messages,
      stream: true
    }),
  });

  if (!response.ok) {
    console.error('Error en la respuesta:', await response.text());
    return;
  }

  const reader = response.body.getReader();
  const decoder = new TextDecoder('utf-8');
  let assistantMessage = '';

  while (true) {
    const { done, value } = await reader.read();
    if (done) break;

    if (value) {
      const chunk = decoder.decode(value, { stream: true });
      const chunks = chunk.split("\n").filter(line => line.trim() !== "");

      chunks.forEach(line => {
        if (line.startsWith('data:')) {
          const json = line.substring(5).trim();

          if (json !== '[DONE]') {
            const data = JSON.parse(json);
            const content = data.choices[0]?.delta?.content || '';

            assistantMessage += content;
            const updatedMessages = [...messages];

            updatedMessages[updatedMessages.length] = {
              role: 'assistant',
              content: assistantMessage
            };

            setMessages(updatedMessages);
          }
        }
      });
    }
  }
}
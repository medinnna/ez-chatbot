const raw = JSON.parse(
  document.getElementById('wp-script-module-data-ez_chatbot')?.textContent ??
    '{}'
)

const chatbotSettings = {
  base_url: raw.base_url ?? '',
  assets_url: raw.assets_url ?? '',
  enabled: raw.enabled ?? '',
  image: raw.image ?? '',
  name: raw.name ?? 'EZ Chatbot',
  color: raw.color ?? '#000',
  notifications: raw.notifications ?? '',
  welcome: raw.welcome ?? '¡Hola! Soy un asistente virtual.',
  placeholder: raw.placeholder ?? '¿En qué te puedo ayudar?',
}

export default chatbotSettings

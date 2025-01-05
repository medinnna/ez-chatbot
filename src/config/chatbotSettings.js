const chatbotSettings = {
  base_url: window.ez_chatbot_settings?.base_url ?? '',
  assets_url: window.ez_chatbot_settings?.assets_url ?? '',
  enabled: window.ez_chatbot_settings?.enabled ?? true,
  image: window.ez_chatbot_settings?.image ?? '',
  name: window.ez_chatbot_settings?.name ?? 'EZ Chatbot',
  color: window.ez_chatbot_settings?.color ?? '#000',
  welcome:
    window.ez_chatbot_settings?.welcome ?? '¡Hola! Soy un asistente virtual.',
  placeholder:
    window.ez_chatbot_settings?.placeholder ?? '¿En qué te puedo ayudar?',
}

export default chatbotSettings

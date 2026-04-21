import { createIcons, X, Send, MessageSquare } from 'lucide'
import './sass/admin/pages/settings.sass'

document.addEventListener('DOMContentLoaded', function () {
  const chatbotWrapper = document.querySelector('.chatbot__widget')
  const imageBtn = document.querySelector('.ez_chatbot_image_select')
  const imageField = document.querySelector('.ez_chatbot_image_upload')
  const imagePreview = chatbotWrapper.querySelector('.profile')
  const imagePreview2 = document.querySelector('.ez_chatbot_image')
  const profileName = document.querySelector('#ez_chatbot_profile_name')
  const profileNamePreview = chatbotWrapper.querySelector('header p')
  const colorField = document.querySelector('#ez_chatbot_color')
  const welcomeMessage = document.querySelector('#ez_chatbot_welcome')
  const welcomeMessagePreview = chatbotWrapper.querySelector('.messages p')
  const submitBtn = document.querySelector('.ez-chatbot__admin .submit')

  createIcons({
    icons: {
      X,
      Send,
      MessageSquare,
    },
  })

  chatbotWrapper.classList.add('is-open')

  imageBtn?.addEventListener('click', (e) => {
    const image = wp
      .media({
        multiple: false,
      })
      .open()
      .on('select', function () {
        const imageSelected = image.state().get('selection').first()

        imagePreview.src = imageSelected.toJSON().url
        imagePreview2.src = imageSelected.toJSON().url
        imagePreview2.classList.remove('hidden')
        imageField.value = imageSelected.toJSON().url
        submitBtn.classList.add('active')
      })

    e.preventDefault()
  })

  document.addEventListener('input', (e) => {
    if (e.target === profileName)
      profileNamePreview.innerText = profileName.value

    if (e.target === colorField)
      chatbotWrapper.style.setProperty('--color', colorField.value)

    if (e.target === welcomeMessage)
      welcomeMessagePreview.innerText = welcomeMessage.value

    submitBtn.classList.add('active')
  })
})

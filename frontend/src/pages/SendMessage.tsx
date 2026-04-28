import { useState } from 'react';
import type { FormEvent } from 'react';
import { whatsappApi } from '../api/whatsapp';
import toast from 'react-hot-toast';

type MessageType = 'text' | 'template' | 'media' | 'buttons';

interface TemplateParam {
  name: string;
  value: string;
}

interface ButtonItem {
  text: string;
}

export default function SendMessage() {
  const [messageType, setMessageType] = useState<MessageType>('text');
  const [phone, setPhone] = useState('');
  const [message, setMessage] = useState('');
  const [templateName, setTemplateName] = useState('');
  const [templateParams, setTemplateParams] = useState<TemplateParam[]>([]);
  const [fileUrl, setFileUrl] = useState('');
  const [buttons, setButtons] = useState<ButtonItem[]>([{ text: '' }]);
  const [loading, setLoading] = useState(false);

  const addTemplateParam = () => {
    setTemplateParams([...templateParams, { name: '', value: '' }]);
  };

  const removeTemplateParam = (index: number) => {
    setTemplateParams(templateParams.filter((_, i) => i !== index));
  };

  const updateTemplateParam = (index: number, field: keyof TemplateParam, value: string) => {
    const updated = [...templateParams];
    updated[index][field] = value;
    setTemplateParams(updated);
  };

  const addButton = () => {
    if (buttons.length < 3) {
      setButtons([...buttons, { text: '' }]);
    }
  };

  const removeButton = (index: number) => {
    if (buttons.length > 1) {
      setButtons(buttons.filter((_, i) => i !== index));
    }
  };

  const updateButton = (index: number, value: string) => {
    const updated = [...buttons];
    updated[index].text = value;
    setButtons(updated);
  };

  const resetForm = () => {
    setPhone('');
    setMessage('');
    setTemplateName('');
    setTemplateParams([]);
    setFileUrl('');
    setButtons([{ text: '' }]);
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      switch (messageType) {
        case 'text':
          await whatsappApi.sendMessage({ phone, message });
          break;
        case 'template':
          await whatsappApi.sendTemplate({
            phone,
            template_name: templateName,
            parameters: templateParams.length > 0 ? templateParams : undefined,
          });
          break;
        case 'media':
          await whatsappApi.sendMedia({ phone, file_url: fileUrl });
          break;
        case 'buttons':
          await whatsappApi.sendButtons({ phone, message, buttons });
          break;
      }
      toast.success('Message queued for delivery!');
      resetForm();
    } catch (error: unknown) {
      const err = error as { response?: { data?: { message?: string } } };
      toast.error(err.response?.data?.message || 'Failed to send message');
    } finally {
      setLoading(false);
    }
  };

  const messageTypes: { value: MessageType; label: string; description: string }[] = [
    { value: 'text', label: 'Text Message', description: 'Send a plain session message' },
    { value: 'template', label: 'Template', description: 'Send a pre-approved template' },
    { value: 'media', label: 'Media/File', description: 'Send a document or image' },
    { value: 'buttons', label: 'Interactive Buttons', description: 'Send a message with buttons' },
  ];

  return (
    <div className="max-w-3xl">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Send Message</h1>
        <p className="text-gray-500 mt-1">Compose and send WhatsApp messages via WATI</p>
      </div>

      {/* Message Type Selector */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        {messageTypes.map((type) => (
          <button
            key={type.value}
            onClick={() => setMessageType(type.value)}
            className={`p-3 rounded-xl border-2 text-left transition-all ${
              messageType === type.value
                ? 'border-whatsapp-500 bg-whatsapp-50'
                : 'border-gray-200 hover:border-gray-300'
            }`}
          >
            <p className={`text-sm font-semibold ${messageType === type.value ? 'text-whatsapp-700' : 'text-gray-700'}`}>
              {type.label}
            </p>
            <p className="text-xs text-gray-500 mt-0.5">{type.description}</p>
          </button>
        ))}
      </div>

      {/* Form */}
      <div className="card">
        <form onSubmit={handleSubmit} className="space-y-5">
          {/* Phone Number */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
            <input
              type="text"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              className="input-field"
              placeholder="+919876543210"
              required
            />
            <p className="text-xs text-gray-400 mt-1">International format with country code</p>
          </div>

          {/* Text Message Fields */}
          {(messageType === 'text' || messageType === 'buttons') && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Message</label>
              <textarea
                value={message}
                onChange={(e) => setMessage(e.target.value)}
                className="input-field min-h-[100px] resize-y"
                placeholder="Type your message..."
                required
                maxLength={messageType === 'buttons' ? 1024 : 4096}
              />
              <p className="text-xs text-gray-400 mt-1">
                {message.length}/{messageType === 'buttons' ? 1024 : 4096} characters
              </p>
            </div>
          )}

          {/* Template Fields */}
          {messageType === 'template' && (
            <>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Template Name</label>
                <input
                  type="text"
                  value={templateName}
                  onChange={(e) => setTemplateName(e.target.value)}
                  className="input-field"
                  placeholder="welcome_message"
                  required
                />
              </div>

              <div>
                <div className="flex items-center justify-between mb-2">
                  <label className="block text-sm font-medium text-gray-700">Parameters (optional)</label>
                  <button type="button" onClick={addTemplateParam} className="text-xs text-whatsapp-600 hover:text-whatsapp-700 font-medium">
                    + Add Parameter
                  </button>
                </div>
                {templateParams.map((param, index) => (
                  <div key={index} className="flex items-center gap-2 mb-2">
                    <input
                      type="text"
                      value={param.name}
                      onChange={(e) => updateTemplateParam(index, 'name', e.target.value)}
                      className="input-field flex-1"
                      placeholder="Parameter name"
                    />
                    <input
                      type="text"
                      value={param.value}
                      onChange={(e) => updateTemplateParam(index, 'value', e.target.value)}
                      className="input-field flex-1"
                      placeholder="Parameter value"
                    />
                    <button
                      type="button"
                      onClick={() => removeTemplateParam(index)}
                      className="text-red-500 hover:text-red-700 p-2"
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                ))}
              </div>
            </>
          )}

          {/* Media Fields */}
          {messageType === 'media' && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">File URL</label>
              <input
                type="url"
                value={fileUrl}
                onChange={(e) => setFileUrl(e.target.value)}
                className="input-field"
                placeholder="https://example.com/document.pdf"
                required
              />
              <p className="text-xs text-gray-400 mt-1">Direct URL to the file (image, PDF, document)</p>
            </div>
          )}

          {/* Buttons Fields */}
          {messageType === 'buttons' && (
            <div>
              <div className="flex items-center justify-between mb-2">
                <label className="block text-sm font-medium text-gray-700">Buttons (max 3)</label>
                {buttons.length < 3 && (
                  <button type="button" onClick={addButton} className="text-xs text-whatsapp-600 hover:text-whatsapp-700 font-medium">
                    + Add Button
                  </button>
                )}
              </div>
              {buttons.map((button, index) => (
                <div key={index} className="flex items-center gap-2 mb-2">
                  <input
                    type="text"
                    value={button.text}
                    onChange={(e) => updateButton(index, e.target.value)}
                    className="input-field flex-1"
                    placeholder={`Button ${index + 1} text`}
                    required
                    maxLength={20}
                  />
                  {buttons.length > 1 && (
                    <button
                      type="button"
                      onClick={() => removeButton(index)}
                      className="text-red-500 hover:text-red-700 p-2"
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  )}
                </div>
              ))}
              <p className="text-xs text-gray-400">Each button text max 20 characters</p>
            </div>
          )}

          {/* Submit */}
          <div className="flex items-center gap-3 pt-2">
            <button
              type="submit"
              disabled={loading}
              className="btn-primary flex items-center gap-2"
            >
              {loading ? (
                <>
                  <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  Sending...
                </>
              ) : (
                <>
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                  </svg>
                  Send Message
                </>
              )}
            </button>
            <button type="button" onClick={resetForm} className="btn-secondary">
              Reset
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

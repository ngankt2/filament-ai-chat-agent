🧠 Filament AI Chat Agent
Tích hợp trợ lý AI (OpenAI, Gemini, ...) vào Filament Admin Panel

🚀 Tính năng

✅ Tích hợp trực tiếp với Filament Panel

🔌 Hỗ trợ đa nhà cung cấp: OpenAI, Gemini, dễ mở rộng Claude, Mistral, v.v.

💬 Giao diện Livewire gọn nhẹ, hỗ trợ panel nhỏ gọn di chuyển & ẩn hiện

🌐 Theo dõi nội dung trang (Page Watcher) làm ngữ cảnh cho AI

🗣️ Tùy chỉnh lời chào, nút gửi, biểu tượng, avatar...

📦 Cài đặt

````
composer require ngankt2/filament-ai-chat-agent
````

🛠 Publish cấu hình và ngôn ngữ
````
php artisan vendor:publish --tag=filament-ai-chat-agent-config
````
````
php artisan vendor:publish --tag=filament-ai-chat-agent-translations
````

⚙️ Cấu hình .env

````
AI_DEFAULT_PROVIDER=openai

OPENAI_API_KEY=sk-xxx
OPENAI_API_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-4o

GEMINI_API_KEY=your-gemini-key
GEMINI_API_BASE_URL=https://generativelanguage.googleapis.com/v1beta/models/
GEMINI_MODEL=gemini-pro
````
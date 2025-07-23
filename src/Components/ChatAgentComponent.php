<?php

namespace Ngankt2\FilamentChatAgent\Components;
use Ngankt2\FilamentChatAgent\FilamentChatAgentPlugin;
use OpenAI;
use Livewire\Attributes\Session;
use Livewire\Component;

class ChatAgentComponent extends Component
{

    public string $name;

    public string $buttonText;

    public string $buttonIcon;

    public string $sendingText;

    public array $messages;

    #[Session]
    public string $question;

    public string $questionContext;

    public string $pageWatcherEnabled;

    public string $pageWatcherSelector;

    public string $winWidth;

    public string $winPosition;

    public bool $showPositionBtn;

    public bool $panelHidden;

    public string|bool $logoUrl;

    private string $sessionKey;

    public function __construct()
    {
        $this->sessionKey = auth()->id() . '-chatgpt-agent-messages-xx';
    }

    public function mount(): void
    {
        $this->panelHidden = session($this->sessionKey . '-panelHidden', true);
        $this->winWidth = "width:" . filament('filament-ai-chat-agent')->getDefaultPanelWidth() . ";";
        $this->winPosition = session($this->sessionKey . '-winPosition', '');
        $this->showPositionBtn = true;
        $this->messages = session(
            $this->sessionKey,
            $this->getDefaultMessages()
        );
        $this->question = "";
        $this->name = filament('filament-ai-chat-agent')->getBotName();
        $this->buttonText = filament('filament-ai-chat-agent')->getButtonText();
        $this->buttonIcon = filament('filament-ai-chat-agent')->getButtonIcon();
        $this->sendingText = filament('filament-ai-chat-agent')->getSendingText();
        $this->questionContext = '';
        $this->pageWatcherEnabled = filament('filament-ai-chat-agent')->isPageWatcherEnabled();
        $this->pageWatcherSelector = filament('filament-ai-chat-agent')->getPageWatcherSelector();
        $this->logoUrl = filament('filament-ai-chat-agent')->getLogoUrl();
    }

    public function render()
    {
        return view('filament-ai-chat-agent::livewire.chat-bot');
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->question))) {
            $this->question = "";
            return;
        }
        $this->messages[] = [
            "role" => 'user',
            "content" => $this->question,
        ];

        $this->chat();
        $this->question = "";
        $this->dispatch('sendmessage', ['message' => $this->question]);
    }

    public function changeWinWidth(): void
    {
        if ($this->winWidth == "width:" . filament('filament-ai-chat-agent')->getDefaultPanelWidth() . ";") {
            $this->winWidth = "width:100%;";
            $this->showPositionBtn = false;
        } else {
            $this->winWidth = "width:" . filament('filament-ai-chat-agent')->getDefaultPanelWidth() . ";";
            $this->showPositionBtn = true;
        }
    }

    public function changeWinPosition(): void
    {
        if ($this->winPosition != "left") {
            $this->winPosition = "left";
        } else {
            $this->winPosition = "";
        }
        session([$this->sessionKey . '-winPosition' => $this->winPosition]);
    }

    public function resetSession(): void
    {
        request()->session()->forget($this->sessionKey);
        $this->messages = $this->getDefaultMessages();
    }

    public function togglePanel(): void
    {
        $this->panelHidden = !$this->panelHidden;
        session([$this->sessionKey . '-panelHidden' => $this->panelHidden]);
    }

    protected function chat(): void
    {
        $provider = config('filament-ai-chat-agent.default','openai');
        $config = config("filament-ai-chat-agent.providers.{$provider}");

        $client = \OpenAI::factory()
            ->withApiKey($config['api_key'])
            ->withBaseUri($config['base_url']) // default đã là https://api.openai.com/v1
            ->withHttpClient(new \GuzzleHttp\Client([
                'timeout' => $config['timeout'] ?? 45,
            ]))
            ->make();

        $payload = array_merge([
            'model' => $config['model'],
            'messages' => $this->messages,
        ], $config['options'] ?? []);

        $response = $client->chat()->create($payload);

        $this->messages[] = $response->choices[0]?->message->toArray() ?? [
            'role' => 'assistant',
            'content' => 'Xin lỗi, không có phản hồi từ AI.',
        ];

        request()->session()->put($this->sessionKey, $this->messages);
    }


    protected function getDefaultMessages(): array
    {
        return filament('filament-ai-chat-agent')->getStartMessage() ?
            [
                ['role' => 'assistant', 'content' => filament('filament-ai-chat-agent')->getStartMessage()],
            ] : [];
    }
}

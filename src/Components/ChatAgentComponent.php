<?php

namespace Ngankt2\FilamentChatAgent\Components;

use Ngankt2\FilamentChatAgent\FilamentChatAgentPlugin;
use Ngankt2\FilamentChatAgent\Models\ChatQuestionTemplate;
use OpenAI;
use Livewire\Attributes\Session;
use Livewire\Component;
use Ngankt2\FilamentChatAgent\Actions\AiFunctionAction;
use Illuminate\Support\Facades\Cache;

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
        $this->messages[] =  [
            'role' => 'system',
            'content' => 'Bạn là trợ lý AI phân tích câu hỏi người dùng và xác định ý định (intent) dựa trên các chức năng có sẵn. Trả về ý định và tham số tương ứng bằng cách gọi function phù hợp. Nếu không khớp với function nào, trả về null cho intent.',
        ];
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


        $provider = config('filament-ai-chat-agent.default', 'openai');
        $config = config("filament-ai-chat-agent.providers.{$provider}");

        $client = OpenAI::factory()
            ->withApiKey($config['api_key'])
            ->withBaseUri($config['base_url'])
            ->withHttpClient(new \GuzzleHttp\Client([
                'timeout' => $config['timeout'] ?? 45,
            ]))
            ->make();
        //dd($config['functions']);;
        // Step 1: gọi lần đầu để hỏi ý định
        $response = $client->chat()->create([
            'model' => $config['model'],
            'messages' => $this->messages,
            'functions' => $config['functions'],
            'function_call' => 'auto',
        ]);

        $choice = $response['choices'][0];


        // Step 2: GPT chọn gọi function
        if (
            isset($choice['finish_reason']) &&
            $choice['finish_reason'] === 'function_call'
        ) {
            $functionName = $choice['message']['function_call']['name'];
            $arguments = json_decode($choice['message']['function_call']['arguments'], true);

            // Step 3: map function name => AiFunctionAction class
            $actionClass = collect(filament('filament-ai-chat-agent')->getActions())
                ->first(fn ($class) => class_basename($class) === $functionName);


            if ($actionClass && is_subclass_of($actionClass, AiFunctionAction::class)) {
                /** @var AiFunctionAction $action */
                $action = app($actionClass);
                $result = $action->execute($arguments);

                // Step 4: gửi kết quả action trả về cho GPT để "trả lời tự nhiên"
                $finalResponse = $client->chat()->create([
                    'model' => $config['model'],
                    'messages' => [
                        ...$this->messages,
                        [
                            'role' => 'system',
                            'content' => 'Dựa vào dữ liệu bên dưới, hãy trả lời người dùng một cách tự nhiên, lịch sự, rõ ràng và chi tiết nhất có thể.',
                        ],
                        [
                            'role' => 'function',
                            'name' => $functionName,
                            'content' => json_encode($result),
                        ],
                    ],
                ]);

                $answer = $finalResponse['choices'][0]['message']['content'] ?? 'Không có câu trả lời.';
            } else {
                $answer = "Không tìm thấy chức năng phù hợp để xử lý.";
            }
        } else {
            $answer = $choice['message']['content'] ?? 'Không rõ câu hỏi.';
        }

        // Gán vào session hoặc trả về trực tiếp
        $this->messages[] = [
            'role' => 'assistant',
            'content' => $answer,
        ];

        session()->put($this->sessionKey, $this->messages);
    }

    protected function getDefaultMessages(): array
    {
        return filament('filament-ai-chat-agent')->getStartMessage() ?
            [
                ['role' => 'assistant', 'content' => filament('filament-ai-chat-agent')->getStartMessage()],
            ] : [];
    }
}

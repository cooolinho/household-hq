<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Comment;
use App\Models\CommentableInterface;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class CommentsWidget extends Widget
{
    public int|string|array $columnSpan = 'full';

    #[Locked]
    public ?Model $record = null;

    public string $newComment = '';

    protected string $view = 'filament.admin.widgets.comments-widget';

    public function addComment(): void
    {
        $this->validate([
            'newComment' => ['required', 'string', 'min:1', 'max:2000'],
        ], [
            'newComment.required' => 'Bitte einen Kommentar eingeben.',
            'newComment.max' => 'Der Kommentar darf maximal 2.000 Zeichen lang sein.',
        ]);

        if (!($this->record instanceof CommentableInterface)) {
            return;
        }

        $this->record->comments()->create([
            Comment::user_id => auth()->id(),
            Comment::message => trim($this->newComment),
        ]);

        $this->newComment = '';
    }

    protected function getViewData(): array
    {
        /** @var Collection<int, Comment> $comments */
        $comments = $this->record instanceof CommentableInterface
            ? $this->record->comments()->with(Comment::belongs_to_user)->get()
            : collect();

        return ['comments' => $comments];
    }
}


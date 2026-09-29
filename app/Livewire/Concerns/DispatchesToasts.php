<?php

namespace App\Livewire\Concerns;

trait DispatchesToasts
{
    protected function toast(string $message, string $type = 'success'): void
    {
        $sessionKey = in_array($type, ['error', 'danger'], true) ? 'error' : 'status';

        session()->flash($sessionKey, $message);

        $this->dispatch(
            'toast',
            message: $message,
            type: in_array($type, ['error', 'danger'], true) ? 'error' : $type,
        );
    }

    protected function toastSuccess(string $message): void
    {
        $this->toast($message, 'success');
    }

    protected function toastError(string $message): void
    {
        $this->toast($message, 'error');
    }
}

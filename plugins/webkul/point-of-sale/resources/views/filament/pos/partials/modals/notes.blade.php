<x-filament::modal id="pos-notes" width="xl">
    <x-slot name="heading">
        {{ __('point-of-sale::filament/pos/pages/terminal.notes.heading') }}
    </x-slot>

    <div class="flex flex-col gap-3">
        @php($notes = $this->getNotes())

        @if ($notes->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach ($notes as $note)
                    <x-filament::badge
                        tag="button"
                        size="lg"
                        :color="$this->noteColor($note->name)"
                        :icon="$this->isNoteSelected($note->name) ? 'heroicon-m-check' : null"
                        wire:click="toggleNote({{ $note->getKey() }})"
                    >
                        {{ $note->name }}
                    </x-filament::badge>
                @endforeach
            </div>
        @endif

        <x-filament::input.wrapper>
            <textarea
                rows="4"
                wire:model="noteDraft"
                placeholder="{{ __('point-of-sale::filament/pos/pages/terminal.notes.placeholder') }}"
                class="block w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 outline-none sm:text-sm dark:text-white"
            ></textarea>
        </x-filament::input.wrapper>
    </div>

    <x-slot name="footerActions">
        <x-filament::button wire:click="applyNote">
            {{ __('point-of-sale::filament/pos/pages/terminal.notes.apply') }}
        </x-filament::button>

        <x-filament::button color="gray" wire:click="discardNote">
            {{ __('point-of-sale::filament/pos/pages/terminal.notes.discard') }}
        </x-filament::button>
    </x-slot>
</x-filament::modal>

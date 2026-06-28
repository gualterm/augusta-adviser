<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @foreach ($this->getWidgets() as $widget)
            @livewire($widget, [], key($widget))
        @endforeach
    </div>
</x-filament-panels::page>
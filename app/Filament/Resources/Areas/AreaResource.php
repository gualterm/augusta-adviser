<?php
namespace App\Filament\Resources\Areas;
use App\Models\Area;
use App\Filament\Resources\Areas\Pages\ListAreas;
use App\Filament\Resources\Areas\Pages\CreateArea;
use App\Filament\Resources\Areas\Pages\EditArea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Icons\Heroicon;
class AreaResource extends Resource {
    protected static ?string $model = Area::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
    protected static ?string $navigationLabel = "Áreas";
    protected static string|\UnitEnum|null $navigationGroup = "Configurações";
    protected static ?int $navigationSort = 10;
    public static function form(Schema $schema): Schema {
        return $schema->components([
            TextInput::make("name")->label("Nome")->required()->maxLength(100),
        ]);
    }
    public static function table(Table $table): Table {
        return $table->columns([
            TextColumn::make("name")->label("Nome")->searchable()->sortable(),
            TextColumn::make("employees_count")->label("Profissionais")->counts("employees"),
            TextColumn::make("services_count")->label("Serviços")->counts("services"),
        ])->actions([EditAction::make()])->bulkActions([DeleteBulkAction::make()]);
    }
    public static function getPages(): array {
        return [
            "index"  => ListAreas::route("/"),
            "create" => CreateArea::route("/create"),
            "edit"   => EditArea::route("/{record}/edit"),
        ];
    }
}
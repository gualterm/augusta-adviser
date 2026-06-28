<?php
namespace App\Filament\Resources\Employees\Schemas;
use App\Models\Employee;
use App\Models\Service;
use App\Models\Area;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dados do Profissional')
                ->schema([
                    Select::make('user_id')
                        ->label('Conta de utilizador')
                        ->placeholder('Sem conta associada')
                        ->helperText('Associa a uma conta de login — os dados sincronizam automaticamente')
                        ->options(function ($record) {
                            $taken = Employee::whereNotNull('user_id')
                                ->when($record?->id, fn ($q) => $q->where('id', '!=', $record->id))
                                ->pluck('user_id');
                            return User::whereNotIn('id', $taken)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->unique(table: Employee::class, column: 'user_id', ignorable: fn ($record) => $record)
                        ->validationMessages(['unique' => 'Este utilizador já está associado a outro profissional.']),
                    TextInput::make('name')
                        ->label('Nome')->required()->maxLength(255),
                    TextInput::make('role')
                        ->label('Especialidade')->maxLength(100)
                        ->datalist(
                            Employee::query()->whereNotNull('role')->where('role','!=','')
                                ->distinct()->orderBy('role')->pluck('role')->toArray()
                        )
                        ->placeholder('Ex: Esteticista, Massagista, Manicure...'),
                    Select::make('areas')
                        ->label('Áreas de Atuação')
                        ->relationship('areas', 'name')
                        ->multiple()->preload()
                        ->helperText('Áreas em que este profissional trabalha'),
                    TextInput::make('phone')
                        ->label('Telefone')->tel()->maxLength(50)
                        ->unique(table: Employee::class, column: 'phone', ignorable: fn ($record) => $record)
                        ->validationMessages(['unique' => 'Este telefone já está associado a outro profissional.']),
                    TextInput::make('email')
                        ->label('Email')->email()->maxLength(255)
                        ->unique(table: Employee::class, column: 'email', ignorable: fn ($record) => $record)
                        ->validationMessages(['unique' => 'Este email já está associado a outro profissional.']),
                    TextInput::make('nif')
                        ->label('NIF')->maxLength(20)
                        ->unique(table: Employee::class, column: 'nif', ignorable: fn ($record) => $record)
                        ->validationMessages(['unique' => 'Este NIF já está associado a outro profissional.']),
                    TextInput::make('default_commission_percentage')
                        ->label('% Comissão Padrão')
                        ->numeric()->suffix('%')->default(0)->minValue(0)->maxValue(100)
                        ->helperText('Pode ser 0 — por ex. para a dona/sócia.'),
                    Toggle::make('active')->label('Ativo')->default(true),
                ])->columns(2),
            Section::make('Comissões por Categoria (opcional)')
                ->description('% diferente da padrão para categorias específicas.')
                ->schema([
                    Repeater::make('commissions')
                        ->relationship('commissions')->label('')
                        ->schema([
                            Select::make('category')->label('Categoria')
                                ->searchable()->preload()
                                ->options(Service::query()->distinct()->orderBy('category')
                                    ->pluck('category','category')->toArray())
                                ->required(),
                            TextInput::make('percentage')->label('% Comissão')
                                ->numeric()->suffix('%')->minValue(0)->maxValue(100)->required(),
                        ])->columns(2)->addActionLabel('Adicionar categoria')->defaultItems(0),
                ]),
        ]);
    }
}
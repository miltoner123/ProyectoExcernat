<?php

namespace App\Console\Commands;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CrearAdminExcernat extends Command
{
    protected $signature = 'excernat:crear-admin';
    protected $description = 'Crea la cuenta administrativa inicial vinculada a una persona existente';

    public function handle(): int
    {
        $id = $this->ask('ID de la persona registrada en EXCERNAT');
        $persona = Persona::find($id);
        if (!$persona || !$persona->estado) {
            $this->error('La persona no existe o está inactiva.');
            return self::FAILURE;
        }
        if (User::where('id_persona', $persona->id_persona)->exists()) {
            $this->error('Esta persona ya tiene una cuenta de usuario.');
            return self::FAILURE;
        }
        $email = trim((string) $this->ask('Correo electrónico para iniciar sesión'));
        $password = (string) $this->secret('Contraseña (mínimo 12 caracteres)');
        $confirmacion = (string) $this->secret('Repita la contraseña');
        if ($password !== $confirmacion) {
            $this->error('Las contraseñas no coinciden.');
            return self::FAILURE;
        }
        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')], 'password' => ['required', 'string', 'min:12']]
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) $this->error($error);
            return self::FAILURE;
        }
        if (!$this->confirm('¿Crear esta cuenta administrativa inicial?', false)) return self::SUCCESS;
        $nombre = trim($persona->nombres_razon_social.' '.($persona->apellidos ?? ''));
        $usuario = User::create([
            'id_persona' => $persona->id_persona,
            'name' => $nombre,
            'email' => $email,
            'password' => Hash::make($password),
            'estado' => true,
        ]);
        $this->info('Cuenta creada correctamente. ID: '.$usuario->id);
        $this->warn('ATENCIÓN: la cuenta aún NO tiene el rol ADMIN; se asignará después de instalar Spatie.');
        return self::SUCCESS;
    }
}

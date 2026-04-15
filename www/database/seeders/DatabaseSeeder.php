<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario admin base (password: "password")
        DB::statement("
            INSERT INTO public.users (id, name, email, password, is_login_directory_active, created_at, updated_at)
            VALUES (1, 'Administrador', 'admin@testimonios.local',
                    '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                    false, NOW(), NOW())
            ON CONFLICT DO NOTHING
        ");

        DB::statement("SELECT setval('users_id_seq', (SELECT MAX(id) FROM public.users))");

        // Entrevistador asociado al admin (nivel 1 = superadmin)
        DB::statement("
            INSERT INTO esclarecimiento.entrevistador (id_entrevistador, id_usuario, id_nivel, solo_lectura, compromiso_reserva)
            VALUES (1, 1, 1, 0, NOW())
            ON CONFLICT DO NOTHING
        ");

        DB::statement("SELECT setval('esclarecimiento.entrevistador_id_entrevistador_seq',
            (SELECT MAX(id_entrevistador) FROM esclarecimiento.entrevistador))");
    }
}

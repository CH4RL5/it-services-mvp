<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateExpert extends Component
{
    public $name, $email, $phone, $expertise;
    public $password = 'expert123'; // Contraseña por defecto

    public function create()
    {
        $this->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'required',
            'expertise' => 'required'
        ]);

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'role' => 'expert',
            'expertise' => $this->expertise,
            'is_online' => true
        ]);

        session()->flash('message', '¡Experto creado correctamente!');
        $this->reset();
    }
    public function delete($id)
    {
        $expert = User::find($id);

        // Evitar borrar cosas que no son expertos
        if ($expert && $expert->role === 'expert') {
            $expert->delete();
            session()->flash('message', 'Experto eliminado correctamente.');
        }
    }

    public function render()
    {
        return view('livewire.admin.create-expert', [
            // Pasamos la lista de expertos a la vista
            'experts' => User::where('role', 'expert')->latest()->get()
        ]);
    }
    public function mount()
    {
        // SEGURIDAD: Si no es admin, prohibir el acceso y lanzar error 403
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Acceso no autorizado.');
        }
    }
}

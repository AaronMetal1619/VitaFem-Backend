<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CitaConfirmada extends Mailable
{
    use Queueable, SerializesModels;

    public $citaData; // Aquí guardamos los datos del paciente y la cita

    public function __construct($citaData)
    {
        $this->citaData = $citaData;
    }

    // ESTE ES EL MÉTODO QUE TU VERSIÓN DE LARAVEL ESTABA PIDIENDO A GRITOS
    public function build()
    {
        return $this->subject('Confirmación de Cita - Clínica VitaFem')
                    ->view('emails.cita_confirmada');
    }
}

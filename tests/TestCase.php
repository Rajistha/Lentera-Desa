<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pengaman: pengujian memakai RefreshDatabase yang mengosongkan seluruh tabel.
     * Berhenti sebelum itu terjadi bila koneksi bukan SQLite di memori, sehingga
     * basis data MySQL asli (data warga dan surat) tidak pernah tersentuh.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $koneksi = $app['config']->get('database.default');
        $basisData = $app['config']->get("database.connections.{$koneksi}.database");

        if ($koneksi !== 'sqlite' || $basisData !== ':memory:') {
            throw new RuntimeException(
                "Pengujian dibatalkan: koneksi \"{$koneksi}\" ({$basisData}) bukan SQLite di memori. "
                .'Periksa phpunit.xml dan pastikan tidak ada variabel lingkungan DB_* yang menimpanya.'
            );
        }

        return $app;
    }
}

<?php

namespace App\Support\Database;

use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Query\Builder;

/**
 * Repository riwayat migrasi dengan skema berbahasa Indonesia.
 *
 * Tabel: riwayat_migrasi(id, nama_migrasi, kelompok). Migrator Laravel mengharapkan objek
 * dengan properti `migration` dan `batch`; kelas ini memetakan kedua nama tersebut ke kolom
 * Indonesia sehingga perintah migrate, migrate:status, migrate:rollback, migrate:reset,
 * dan migrate:fresh tetap berfungsi.
 */
class RepositoriMigrasi extends DatabaseMigrationRepository
{
    private const KOLOM_NAMA = 'nama_migrasi';

    private const KOLOM_KELOMPOK = 'kelompok';

    /** @return list<string> */
    public function getRan()
    {
        return $this->table()
            ->orderBy(self::KOLOM_KELOMPOK)
            ->orderBy(self::KOLOM_NAMA)
            ->pluck(self::KOLOM_NAMA)
            ->all();
    }

    public function getMigrations($steps)
    {
        return $this->dipetakan()
            ->where(self::KOLOM_KELOMPOK, '>=', 1)
            ->orderBy(self::KOLOM_KELOMPOK, 'desc')
            ->orderBy(self::KOLOM_NAMA, 'desc')
            ->limit($steps)
            ->get()
            ->all();
    }

    public function getMigrationsByBatch($batch)
    {
        return $this->dipetakan()
            ->where(self::KOLOM_KELOMPOK, $batch)
            ->orderBy(self::KOLOM_NAMA, 'desc')
            ->get()
            ->all();
    }

    public function getLast()
    {
        return $this->dipetakan()
            ->where(self::KOLOM_KELOMPOK, $this->getLastBatchNumber())
            ->orderBy(self::KOLOM_NAMA, 'desc')
            ->get()
            ->all();
    }

    public function getMigrationBatches()
    {
        return $this->table()
            ->orderBy(self::KOLOM_KELOMPOK)
            ->orderBy(self::KOLOM_NAMA)
            ->pluck(self::KOLOM_KELOMPOK, self::KOLOM_NAMA)
            ->all();
    }

    public function log($file, $batch)
    {
        $this->table()->insert([self::KOLOM_NAMA => $file, self::KOLOM_KELOMPOK => $batch]);
    }

    public function delete($migration)
    {
        $this->table()->where(self::KOLOM_NAMA, $migration->migration)->delete();
    }

    public function getLastBatchNumber()
    {
        return (int) $this->table()->max(self::KOLOM_KELOMPOK);
    }

    public function createRepository()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        $schema->create($this->table, function ($table) {
            $table->increments('id');
            $table->string(self::KOLOM_NAMA);
            $table->integer(self::KOLOM_KELOMPOK);
        });
    }

    /**
     * Query dengan alias kolom standar Laravel agar Migrator dapat membaca hasilnya.
     */
    private function dipetakan(): Builder
    {
        return $this->table()->select([
            'id',
            self::KOLOM_NAMA.' as migration',
            self::KOLOM_KELOMPOK.' as batch',
        ]);
    }
}

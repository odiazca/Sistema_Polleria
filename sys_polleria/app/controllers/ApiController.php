<?php
/** Endpoints JSON compartidos (requieren sesión) */
class ApiController extends Controller
{
    public function clientesAction(): void
    {
        $q = trim($_GET['q'] ?? '');
        if ($q === '') {
            $rows = DB::all('SELECT id, tipo_doc, documento, nombre, telefono, direccion, referencia FROM clientes WHERE estado = 1 ORDER BY id DESC LIMIT 12');
        } else {
            $like = '%' . $q . '%';
            $rows = DB::all('SELECT id, tipo_doc, documento, nombre, telefono, direccion, referencia FROM clientes WHERE estado = 1 AND (nombre LIKE ? OR documento LIKE ? OR telefono LIKE ?) ORDER BY nombre LIMIT 20', [$like, $like, $like]);
        }
        $this->json(['ok' => true, 'data' => $rows]);
    }

    public function clienteNuevoAction(): void
    {
        $d = $this->body();
        $nombre = trim($d['nombre'] ?? '');
        if ($nombre === '') $this->json(['ok' => false, 'msg' => 'El nombre es obligatorio.']);
        $tipo = in_array($d['tipo_doc'] ?? '', ['DNI', 'RUC', 'CE', 'OTRO'], true) ? $d['tipo_doc'] : 'DNI';
        $doc = trim($d['documento'] ?? '');
        if ($doc !== '' && ($ex = DB::one('SELECT id, tipo_doc, documento, nombre, telefono, direccion, referencia FROM clientes WHERE documento = ? LIMIT 1', [$doc]))) {
            $this->json(['ok' => true, 'data' => $ex, 'msg' => 'Ya existía un cliente con ese documento.']);
        }
        $id = DB::insert('clientes', [
            'tipo_doc' => $tipo, 'documento' => $doc ?: null, 'nombre' => $nombre,
            'telefono' => trim($d['telefono'] ?? '') ?: null, 'direccion' => trim($d['direccion'] ?? '') ?: null,
        ]);
        $this->json(['ok' => true, 'data' => DB::one('SELECT id, tipo_doc, documento, nombre, telefono, direccion, referencia FROM clientes WHERE id = ?', [$id])]);
    }
}

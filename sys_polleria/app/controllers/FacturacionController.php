<?php
/** Facturación electrónica SUNAT (boletas y facturas) */
class FacturacionController extends Controller
{
    protected string $module = 'facturacion';

    public function indexAction(): void
    {
        $desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde'] ?? '') ? $_GET['desde'] : date('Y-m-d', strtotime('-6 days'));
        $hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta'] ?? '') ? $_GET['hasta'] : date('Y-m-d');
        $est = in_array($_GET['estado'] ?? '', ['pendiente', 'aceptado', 'rechazado', 'error', 'anulado', 'sinenvio'], true) ? $_GET['estado'] : '';
        $w = ["p.comprobante IN ('boleta','factura')", "p.estado IN ('pagado','anulado')", 'DATE(p.fecha_pago) BETWEEN ? AND ?']; $par = [$desde, $hasta];
        if ($est === 'sinenvio') $w[] = "p.fe_estado IS NULL AND p.estado = 'pagado'";
        elseif ($est) { $w[] = 'p.fe_estado = ?'; $par[] = $est; }
        $rows = DB::all('SELECT p.*, c.nombre AS cliente, c.documento FROM pedidos p LEFT JOIN clientes c ON c.id = p.cliente_id WHERE ' . implode(' AND ', $w) . ' ORDER BY p.id DESC LIMIT 500', $par);
        $res = DB::all("SELECT COALESCE(fe_estado,'sin') k, COUNT(*) n FROM pedidos WHERE comprobante IN ('boleta','factura') AND estado = 'pagado' AND DATE(fecha_pago) BETWEEN ? AND ? GROUP BY k", [$desde, $hasta]);
        $cnt = array_column($res, 'n', 'k');
        $this->view('facturacion/index', ['title' => 'Facturación electrónica', 'rows' => $rows, 'cnt' => $cnt, 'f' => compact('desde', 'hasta', 'est')]);
    }

    public function enviarAction(): void
    {
        $r = Sunat::enviar((int)($this->body()['id'] ?? $_POST['id'] ?? 0));
        $this->json($r);
    }

    public function enviarPendientesAction(): void
    {
        $ids = DB::all("SELECT id FROM pedidos WHERE comprobante IN ('boleta','factura') AND estado = 'pagado' AND (fe_estado IS NULL OR fe_estado IN ('pendiente','error','rechazado')) ORDER BY id LIMIT 40");
        $ok = 0; $fail = 0;
        foreach ($ids as $i) { $r = Sunat::enviar((int)$i['id']); $r['estado'] === 'aceptado' ? $ok++ : $fail++; }
        flash($fail ? 'warning' : 'success', "Envío masivo: $ok aceptados" . ($fail ? ", $fail con observaciones (revise el detalle)." : '.'));
        redirect('facturacion/index');
    }

    public function configAction(): void
    {
        $this->view('facturacion/config', ['title' => 'Configuración de facturación', 'tokenSet' => cfg('fe_token', '') !== '']);
    }

    public function guardarAction(): void
    {
        $url = trim((string)($_POST['fe_url'] ?? ''));
        if ($url !== '' && !preg_match('#^https://[^\s]+$#i', $url)) { flash('danger', 'La ruta del proveedor debe empezar con https://'); redirect('facturacion/config'); }
        Setting::set('fe_activo', isset($_POST['fe_activo']) ? '1' : '0');
        Setting::set('fe_auto', isset($_POST['fe_auto']) ? '1' : '0');
        Setting::set('fe_modo', ($_POST['fe_modo'] ?? '') === 'nubefact' ? 'nubefact' : 'simulado');
        Setting::set('fe_url', $url);
        if (trim((string)($_POST['fe_token'] ?? '')) !== '') Setting::set('fe_token', trim($_POST['fe_token']));
        Log::add('config', 'Configuración de facturación electrónica actualizada');
        flash('success', 'Configuración de facturación guardada.');
        redirect('facturacion/config');
    }
}

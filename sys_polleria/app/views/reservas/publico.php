<?php /** Página pública de reservas (móvil primero) */
$empresa = cfg('empresa_nombre', APP_NAME);
$logo = cfg('empresa_logo');
$brand = preg_match('/^#[0-9a-fA-F]{6}$/', cfg('color_principal', '#F97316')) ? cfg('color_principal', '#F97316') : '#F97316';
$tel = cfg('empresa_telefono', ''); $dir = cfg('empresa_direccion', '');
$maxD = date('Y-m-d', strtotime('+' . (int)$rc['dias'] . ' days'));
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reservar mesa · <?= e($empresa) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root{--brand:<?= e($brand) ?>;--bg:#FFF9F2;--ink:#1F2937;--mut:#6B7280;--line:#F1E7DA}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);min-height:100vh;-webkit-tap-highlight-color:transparent}
.top{background:linear-gradient(135deg,#FFEDD5,#FEF3C7);padding:22px 16px 56px;border-radius:0 0 28px 28px;text-align:center}
.lg{width:58px;height:58px;border-radius:16px;background:var(--brand);color:#fff;display:grid;place-items:center;font-size:28px;margin:0 auto 10px;overflow:hidden;box-shadow:0 12px 26px -14px var(--brand)}
.lg img{width:100%;height:100%;object-fit:cover}
h1{font-size:1.25rem;font-weight:800}.top p{color:#92400E;font-size:.86rem;margin-top:4px}
.wrap{max-width:560px;margin:-36px auto 40px;padding:0 14px}
.card{background:#fff;border:1px solid var(--line);border-radius:22px;padding:18px;box-shadow:0 18px 40px -26px rgba(234,88,12,.45);margin-bottom:14px}
.card h2{font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;color:var(--mut);font-weight:800;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.card h2 b{width:22px;height:22px;border-radius:50%;background:var(--brand);color:#fff;display:grid;place-items:center;font-size:.72rem;letter-spacing:0}
label{display:block;font-size:.8rem;font-weight:700;margin:12px 0 5px}
input,select,textarea{width:100%;font:inherit;font-size:.95rem;padding:12px 14px;border:1.5px solid var(--line);border-radius:14px;background:#fff;color:var(--ink);outline:0}
input:focus,select:focus,textarea:focus{border-color:var(--brand);box-shadow:0 0 0 4px color-mix(in srgb,var(--brand) 18%,transparent)}
.step{display:flex;align-items:center;justify-content:space-between;background:#FFF7ED;border-radius:16px;padding:8px}
.step button{width:44px;height:44px;border:0;border-radius:12px;background:#fff;color:var(--brand);font-size:20px;font-weight:800;box-shadow:0 4px 12px -6px rgba(0,0,0,.2)}
.step div{text-align:center}.step b{font-size:1.6rem;font-weight:800}.step small{display:block;color:var(--mut);font-size:.74rem;margin-top:-2px}
.hrs{display:grid;grid-template-columns:repeat(auto-fill,minmax(88px,1fr));gap:8px}
.hr{border:1.5px solid var(--line);background:#fff;border-radius:12px;padding:10px 4px;font:inherit;font-weight:700;font-size:.85rem;color:var(--ink)}
.hr.on{background:var(--brand);border-color:var(--brand);color:#fff}
.hr:disabled{opacity:.4;text-decoration:line-through;background:#F9FAFB}
.msg{font-size:.84rem;color:var(--mut);padding:8px 2px}
.btn{width:100%;border:0;border-radius:16px;padding:15px;font:inherit;font-weight:800;font-size:1rem;color:#fff;background:linear-gradient(135deg,#FB923C,var(--brand));box-shadow:0 14px 26px -14px var(--brand);cursor:pointer}
.btn:disabled{opacity:.55;box-shadow:none}
.err{color:#B91C1C;background:#FEE2E2;border-radius:12px;padding:10px 12px;font-size:.84rem;margin-top:12px;display:none}
.ok{text-align:center;padding:10px 4px}
.ok .ck{width:72px;height:72px;border-radius:50%;background:#D1FAE5;color:#059669;display:grid;place-items:center;font-size:38px;margin:0 auto 12px}
.ok h2{font-size:1.2rem;text-transform:none;letter-spacing:0;color:var(--ink);display:block;margin:0 0 6px}
.cod{display:inline-block;background:#FFF7ED;border:2px dashed var(--brand);color:var(--brand);font-weight:800;font-size:1.4rem;letter-spacing:.12em;padding:8px 20px;border-radius:14px;margin:10px 0}
.res{text-align:left;background:#F9FAFB;border-radius:16px;padding:12px 14px;font-size:.9rem;margin:10px 0}.res div{display:flex;gap:8px;padding:3px 0}.res i{color:var(--brand)}
.foot{text-align:center;color:var(--mut);font-size:.78rem;line-height:1.6}
.hide{display:none!important}
</style>
</head>
<body>
<div class="top">
  <div class="lg"><?php if ($logo): ?><img src="<?= e(upload_url($logo)) ?>" alt=""><?php else: ?><i class="bi bi-fire"></i><?php endif; ?></div>
  <h1><?= e($empresa) ?></h1><p>Reserve su mesa en menos de un minuto</p>
</div>
<div class="wrap">
<?php if (!$activo): ?>
  <div class="card ok"><div class="ck" style="background:#FEF3C7;color:#D97706"><i class="bi bi-calendar-x"></i></div><h2>Reservas en línea no disponibles</h2>
    <p class="foot">Por el momento no recibimos reservas por internet.<?= $tel ? '<br>Llámenos al <b>' . e($tel) . '</b>.' : '' ?></p></div>
<?php else: ?>
  <form id="f" class="">
    <div class="card"><h2><b>1</b>¿Cuántas personas y cuándo?</h2>
      <label style="margin-top:0">Personas</label>
      <div class="step"><button type="button" id="m" aria-label="Menos">−</button><div><b id="pn">2</b><small>personas</small></div><button type="button" id="p" aria-label="Más">+</button></div>
      <label>Fecha</label><input type="date" id="fecha" min="<?= e(date('Y-m-d')) ?>" max="<?= e($maxD) ?>" value="<?= e(date('Y-m-d')) ?>" required>
    </div>
    <div class="card"><h2><b>2</b>Elija la hora</h2><div id="hrs" class="hrs"></div><div id="hm" class="msg"></div></div>
    <div class="card"><h2><b>3</b>Sus datos</h2>
      <label style="margin-top:0">Nombre completo</label><input id="nom" maxlength="120" autocomplete="name" required placeholder="Ej. María Pérez">
      <label>Teléfono / WhatsApp</label><input id="tel" type="tel" maxlength="30" autocomplete="tel" inputmode="tel" required placeholder="987 654 321">
      <label>Ocasión especial (opcional)</label><select id="oc"><option value="">— Ninguna —</option><?php foreach (Reservas::OCASIONES as $o): ?><option><?= e($o) ?></option><?php endforeach; ?></select>
      <label>Comentarios (opcional)</label><textarea id="nt" rows="2" maxlength="300" placeholder="Silla para bebé, alergias, torta…"></textarea>
      <input type="text" id="web" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
      <div class="err" id="er"></div>
    </div>
    <button class="btn" id="go" disabled><i class="bi bi-calendar-check me-1"></i>Reservar mesa</button>
  </form>
  <div id="done" class="card ok hide">
    <div class="ck"><i class="bi bi-check-lg"></i></div><h2>¡Reserva recibida!</h2>
    <p class="foot">Le confirmaremos por WhatsApp o llamada.</p>
    <div class="cod" id="cod"></div>
    <div class="res" id="rs"></div>
    <p class="foot">Guarde este código. Si necesita cambiar su reserva, comuníquese con nosotros<?= $tel ? ' al <b>' . e($tel) . '</b>' : '' ?>.</p>
  </div>
<?php endif; ?>
  <p class="foot" style="margin-top:18px"><?php if ($dir): ?><i class="bi bi-geo-alt"></i> <?= e($dir) ?><br><?php endif; ?>Horario de reservas: <?= e($rc['apertura']) ?> a <?= e($rc['cierre']) ?> · Mesa por <?= (int)$rc['duracion'] ?> min</p>
</div>
<?php if ($activo): ?>
<script>
var CSRF=<?= json_encode(csrf_token()) ?>, BASE=<?= json_encode(BASE_URL) ?>, MAXP=<?= (int)$rc['max'] ?>;
var $=function(i){return document.getElementById(i)}, per=2, hora='';
function api(r,b){var o={headers:{'Accept':'application/json','X-CSRF-Token':CSRF}};if(b){o.method='POST';o.headers['Content-Type']='application/json';o.body=JSON.stringify(b)}
  return fetch(BASE+'/index.php?r='+r,o).then(function(x){return x.json()}).catch(function(){return{ok:false,msg:'Sin conexión. Inténtelo de nuevo.'}})}
function h12(h){var a=+h.slice(0,2),b=h.slice(3);return(a%12||12)+':'+b+(a>=12?' p. m.':' a. m.')}
function pinta(){$('pn').textContent=per;horas()}
function horas(){hora='';$('go').disabled=true;$('hrs').innerHTML='';$('hm').textContent='Buscando horarios…';
  api('reservar/horas&fecha='+$('fecha').value+'&personas='+per).then(function(j){
    if(!j.ok){$('hm').textContent=j.msg||'No se pudo cargar';return}
    var ok=j.horas.filter(function(x){return x.ok}).length;
    $('hm').textContent=ok?'':'No hay horarios disponibles ese día. Pruebe otra fecha o menos personas.';
    $('hrs').innerHTML=j.horas.map(function(x){return '<button type="button" class="hr" data-h="'+x.h+'"'+(x.ok?'':' disabled')+'>'+h12(x.h)+'</button>'}).join('')})}
$('p').onclick=function(){if(per<MAXP){per++;pinta()}};$('m').onclick=function(){if(per>1){per--;pinta()}};
$('fecha').onchange=horas;
$('hrs').onclick=function(e){var b=e.target.closest('.hr');if(!b||b.disabled)return;hora=b.dataset.h;document.querySelectorAll('.hr').forEach(function(x){x.classList.toggle('on',x===b)});$('go').disabled=false};
$('f').onsubmit=function(e){e.preventDefault();var er=$('er');er.style.display='none';
  if(!hora){er.textContent='Elija una hora.';er.style.display='block';return}
  $('go').disabled=true;$('go').textContent='Reservando…';
  api('reservar/crear',{nombre:$('nom').value,telefono:$('tel').value,fecha:$('fecha').value,hora:hora,personas:per,ocasion:$('oc').value,notas:$('nt').value,web:$('web').value}).then(function(j){
    $('go').textContent='Reservar mesa';
    if(!j.ok){er.textContent=j.msg;er.style.display='block';$('go').disabled=false;horas();return}
    $('f').classList.add('hide');$('done').classList.remove('hide');$('cod').textContent=j.codigo;
    var d=new Date($('fecha').value+'T12:00:00');
    $('rs').innerHTML='<div><i class="bi bi-person"></i>'+$('nom').value.replace(/</g,'&lt;')+'</div><div><i class="bi bi-calendar-event"></i>'+d.toLocaleDateString('es-PE',{weekday:'long',day:'numeric',month:'long'})+'</div><div><i class="bi bi-clock"></i>'+h12(hora)+'</div><div><i class="bi bi-people"></i>'+per+' persona(s)</div>';
    window.scrollTo({top:0,behavior:'smooth'})})};
pinta();
</script>
<?php endif; ?>
</body>
</html>

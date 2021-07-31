<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Peer2win-login</title>

  <link rel="stylesheet" href="css/ant-footer.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/footer.css">
  <link rel="stylesheet" href="css/navbar-header-log.css">
  <link rel="stylesheet" href="css/navbar-header-sin-loguear.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/login.css">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Cairo&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css"
    integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">

</head>

<body>
  <?php include 'includes/redes.php';?>
  <?php include 'includes/navbar.php';?>

  <div class="row row-imagen-fondo-1">
    <div class="container-fluid main-container">
      <div class="row">
        <div class="col-12">
          <img loading="lazy" class="logo-p-2-w" src="imagen/Logo-peertowin.png" alt="">
        </div>
      </div>
      <div class="row">
        <div class="col-sm-8 col-md-6 col-lg-4 offset-sm-2 offset-md-6   edit-container-login pr-5">
          <!-- <h6 class="tienes-codigo">¿Tienes un código de un referido? ingrésalo aquí</h6> -->


          <div class="form-group">
            <label class="usuario-login" for="">Usuario</label>
            <input type="text" class="form-control" name="" id="txtuser" aria-describedby="helpId" placeholder="">
          </div>
          <div class="form-group">
            <div class="row">
              <div class="col-6 px-0">
                <label for="">Contraseña</label>
              </div>
              <div class="col-6 text-right px-0">
                <label class="eyepassword" for="">
                  <img loading="lazy" style="" src="imagen/ojo-ocultar.png" id="icon1"
                    onclick="showpassdRegistro('txtpassword', 'icon1', 'icon2')" alt="">
                  <img loading="lazy" style="" src="imagen/ojo-mostrar.png" id="icon2"
                    onclick="unshowpassdRegistro('txtpassword', 'icon2', 'icon1')" alt="">Ver contraseña
                </label>
              </div>
            </div>
            <input type="password" class="form-control input-contrasena-login" name="" id="txtpassword" placeholder="">
          </div>
          <div class="aceptopoliticasde">
            <input type="checkbox" id="check">
            <small id="helpId" class="form-text text-muted">Acepto políticas de confidencialidad</small>
          </div>
          <div class="d-flex justify-content-around">

            <a href=""><small id="helpId" class="form-text text-muted">¿Olvidaste tu usuario?</small></a>
            <a data-toggle="modal" href='#contrasena-login'><small id="helpId" class="form-text text-muted">¿Olvidaste
                tu contraseña?</small></a>

          </div>
          <div class="d-flex btn-login my-3">
            <a class="btn btn-primary btn-ver-login" id="btn-login" role="button">
              Entrar
            </a>
          </div>
        </div>


        <div class="col-12 text-right">

          <div class="banner-llamada-2">
            <a href="./contacto.php">CONTÁCTANOS PARA INICIAR</a>
            <div class="linevertical">

            </div>
            <a href="https://wa.me/573163261371?text=Quiero pertenecer al ecosistema de Peers2win, ¿podrían indicarme como me registro?"
              target="_blank">COMENZAR CHAT <img loading="lazy" style="margin-left: 10px" src="imagen/llamada.png"
                alt=""></a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row row-imagen-login">
    <div class="col-sm-4 offset-sm-6 row-fondo-3-login adicional">
      <h3 class="titulo-todos">Todos somos <br> ganadores en Peers2Win</h3>
      <p>Dependiendo del paquete que obtenga hará parte de un nivel <br> dentro de esta gran empresa.</p>
    </div>
  </div>

  <div class="row" id="membresia">
    <div class="col-12 row-color-3-login text-center">
      <div class="row img-logos-login cargarPlan">
        <div class="col-sm-6 col-lg-3 ">
          <img loading="lazy" src="imagen/Menbresia-Winner.png" alt="">
          <h4 class="lis"> MEMBRESIA <br> WINNER</h4>
          <p class="edit-parraro-winner login-parrafo-winner"><strong>Gana el 5% venta de los productos de la
              compañía </strong> de manera
            directa y ganas el <strong>
              25% de las ventas</strong> de todos
            los negocios que refieras a nuestro marketplace.</p>
          <p class="edit-parraro-winner-2-login">Costo único </p>
          <p class="precio-membrecia precio-membrecia-login" style="text-decoration: none;">39 USD</p>
          <a name="" id="" class="btn btn-primary btn-comenzar-ahora comenzar-ahora-login btn-cont" role="button">
            COMIENZA AHORA
          </a>
        </div>
        <div class="col-sm-6 col-lg-3">
          <img loading="lazy" src="imagen/Winner-promoter.png" alt="">
          <h4> WINNER <br> PROMOTER</h4>
          <ul class="vineta-membresia list-2 text-left">
            <li> Buy in Big</li>
            <li> Bid by Token</li>
            <!-- <li> ICD Básico</li> -->
            <li> Curso trading 1</li>
            <li> Genera 150 Pts</li>
            <li> 10 TKT SUBASTAS</li>
            <li>BRONZE</li>
            <li> Curso marketplace módulo 1</li>
          </ul>
          <p class="precio-membrecia" style="text-decoration: none;">349 USD</p>
          <a name="" id="" class="btn btn-primary btn-comenzar-ahora unico-btn btn-cont" role="button">
            COMIENZA AHORA
          </a>
        </div>
        <div class="col-sm-6 col-lg-3">
          <img loading="lazy" src="imagen/Winner-manager.png" alt="">
          <h4> WINNER <br> MANAGER</h4>
          <ul class="vineta-membresia list-2 text-left">
            <li> Buy in Big</li>
            <li> Bid by Token</li>
            <!-- <li> ICD Básico</li> -->
            <li> Curso trading 2</li>
            <li> Genera 300 Pts</li>
            <li> 10 TKT SUBASTAS</li>
            <li> BRONZE Y SILVER</li>
            <li> Curso marketplace módulo 1 y 2</li>
          </ul>
          <p class="precio-membrecia" style="text-decoration: none;">499 USD</p>
          <a name="" id="" class="btn btn-primary btn-comenzar-ahora unico-btn btn-cont" role="button">
            COMIENZA AHORA
          </a>
        </div>
        <div class="col-sm-6 col-lg-3">
          <img loading="lazy" src="imagen/Winner-superior.png" alt="">
          <h4> WINNER <br> SUPERIOR</h4>
          <ul class="vineta-membresia list-2 text-left">
            <li> Buy in Big</li>
            <li> Bid by Token</li>
            <!-- <li> ICD Básico</li> -->
            <li> Curso trading 3</li>
            <li> Genera 600 Pts</li>
            <li> 10 TKT SUBASTAS</li>
            <li> BRONZE, SILVER, GOLD</li>
            <li> Curso marketplace módulo 1, 2, 3 </li>
          </ul>
          <p class="precio-membrecia" style="text-decoration: none;">999 USD</p>
          <a name="" id="" class="btn btn-primary btn-comenzar-ahora unico-btn btn-cont" role="button">
            COMIENZA AHORA
          </a>
        </div>
      </div>
    </div>

  </div>

  <div class="footer-dashboard">
    <?php include 'includes/ant-footer.php';?>
  </div>

</body>

<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>
<script src="js/owl.carousel.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/index.js"></script>
<script src="js/user.js"></script>

</html>

<div class="modal fade" id="contrasena-login" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered " role="document">
    <div class="modal-content">
      <div class="modal-body body-compartir-tickets recuperar-contrasena">
        <div class="row row-contrasena-login ">
          <div class="col-12  display-correo-electronico">
            <div style="width:100%; text-align: center;">

              <img loading="lazy" class="imagen-recuperar" src="imagen/Logo-peertowin.png" alt="">
            </div>
            <h4>Recuperar Contraseña</h4>
            <p>Escribe el correo electrónico con el que te registraste, se te enviará un nuevo password.</p>
            <h6>Correo electrónico:</h6>
            <input type="text" name="correo" id="correo" maxlength="50">
            <h6>Confirme Correo electrónico:</h6>
            <input type="text" name="correo2" id="correo2" maxlength="50">
            <div class="d-flex justify-content-around align-center" style="width: 100%;">
              <a id="btn-recuperar" class="btn btn-primary btn-contraseña my-3">
                Enviar
              </a>
              <a id="btn-compartir-correo" class="btn btn-primary btn-contrasena-regresar my-3" data-dismiss="modal">
                Regresar
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
foreach (glob("../class/*.php") as $filename) {
   include_once($filename);
}
session_start();
if(isset($_SESSION['usuario']))
{
  $usuario = unserialize($_SESSION['usuario']);
  if(get_class($usuario) == 'jefeDeCarrera') {
    $usuario = new departamento($usuario->getNombre(),$usuario->getNombreUsuario(),$usuario->getRut());
    $_SESSION['usuario'] = serialize($usuario);
    $_SESSION['carrera'] = NULL;
    $_SESSION['codigoSemestre'] = NULL;
  }

  if(isset($_POST['submit']) && $_POST['submit'] == 'Crear') 
  {
    if(isset($_POST['hiddenCodigoRamo']) && isset($_POST['hiddenCodigoSemestre']) && isset($_POST['regimen']))
    {
      $msg = $usuario->crearSeccionDepartamento($_POST['hiddenCodigoRamo'],$_POST['hiddenCodigoSemestre'],$_POST['regimen']);
    }
    else
    {
      $msg = '*Debe seleccionar el regimen del ramo, D = diurno o V = vespertino.';
    }  
  }

  if($_SESSION['tipoUsuario'] == 4)
  {
?>
<!DOCTYPE HTML>
<html>

<head>
  <title>colour_blue</title>
  <meta charset="utf-8" />
  <meta name="description" content="website description" />
  <meta name="keywords" content="website keywords, website keywords" />
  <meta http-equiv="content-type" content="text/html; charset=windows-1252" />
  <link rel="stylesheet" type="text/css" href="../style/style.css" title="style" />
</head>

<body>
  <div id="main">
    <div id="header">
      <div id="logo">
        <div id="logo_text">
          <!-- class="logo_colour", allows you to change the colour of the text -->
          <h1><a href="../index.php">Universidad<span class="logo_colour"> Andrés Bello</span></a></h1>
          <h2>Herramienta de programación de horarios.</h2>
        </div>
      </div>
      <div id="menubar">
        <ul id="menu">
          <li><a href="depto.php">Ramos</a></li>
          <li class="selected"><a href="seccion.php">Secciones</a></li>
          <li><a href="tipos.php">Tipos</a></li>
          <li><a href="../logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
    <div id="site_content">
      <div id="content">
        <!-- insert the page content here -->
        <h1>Secciones</h1>
        <?php
          if(isset($msg))
            echo '<span class="error">'.$msg.'</span>';
          echo '<table>';
          echo '<tr><td>Codigo</td><td>Nombre</td><td>Crear sección</td><td>Seccines creadas</td></tr>';     
          verRamosDepartamento();
          echo '</table>';
        ?>
      </div>
    </div>
    <div id="content_footer"></div>
    <div id="footer">
    </div>
  </div>
  <script type='text/javascript' src='../js/jquery.js'></script> 
  <script type='text/javascript' src='../js/jquery.simplemodal.js'></script> 
  <script type='text/javascript' src='../js/bsc.js'></script></body>
</html><?php
  }
  else
  {
    header("Location: ../index.php");
    exit();
  }
}
else
{
  header("Location: ../index.php");
  exit();
}

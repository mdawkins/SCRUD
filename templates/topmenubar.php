    <!-- top menu bar --!>
    <div class="topnav" id="myTopnav">
    <ul>
     <li><a class="active" href="javascript:void(0)" onclick="openNav()"><i class="fa fa-fw fa-bars"></i>Menu</a></li>
     <li class="dropdown"><a href="javascript:void(0)"><i class="fa fa-fw fa-file"></i>File</a>
        <div class="dropdown-content">
          <a href="javascript:void(0)"><i class="fa fa-fw fa-copy"></i>Copy</a>
          <a href="javascript:void(0)"><i class="fa fa-fw fa-file-excel"></i>Excel</a>
          <a href="javascript:void(0)"><i class="fa fa-fw fa-file-pdf"></i>PDF</a>
          <a href="javascript:void(0)"><i class="fa fa-fw fa-print"></i>Print</a>
        </div>
     </li>
     <li><a href="#news"><i class="fa fa-fw fa-check-square"></i>News</a></li>
     <li><a href="#contact"><i class="fa fa-fw fa-square"></i>Contact</a></li>
     <li><a href="#about"><i class="fa fa-fw fa-home"></i>About</a></li>
     <li><a href="javascript:void(0);" class="icon" onclick="myFunction()"><i class="fa fa-bars"></i></a></li>
    </ul>
    </div>

    <div id="myNav" class="overlay">
     <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
     <div class="overlay-content">
<?php echo $menuhtml; ?>
     </div>
    </div>

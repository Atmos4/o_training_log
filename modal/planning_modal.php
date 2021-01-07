<?php
$structure_users = array();
$req_strusers = get_db()->prepare('SELECT *, UNIX_TIMESTAMP(lastvisit) AS ulast FROM runners WHERE level="USER" AND structure_id =? ORDER BY prenom');
$req_strusers->execute(array($_SESSION['structure_id']));
while($data = $req_strusers->fetch()){
    array_push($structure_users, $data);
}?>

<!--Modal-->
<div id="planning-modal" class="modal">
    <!-- Modal content -->
    <div class="modal-content animatetop">
        <span class="close" onclick="closeModal('planning-modal')">&times;</span>
        <h2 class="aln-center">Ajouter une planification</h2>
        <form id="planningform"  class="container" autocomplete="off" method="post">
            <input name="source" type="hidden" id="formname" value="add-planning-form">
            <input name = "date" type = "hidden" id = "pdate" value="">
            <div class = "row justify-content-center">
            <?php if ($is_admin){?>
                    <label for = "iscommon"> Commun</span>
                    <label class="switch">
                        <input type="checkbox" id = "iscommon" name = "iscommon">
                        <span class="switchslider round"></span>
                    </label>
                <?php /*
                <div class = "dropwrapper create-only">
                    <span>Individualisé : 
                    <span class="multiSel">0</span></span>
                    <img class = "dropbutton" src = "images/add-white.png">
                    <ul class = "dropdown">

                        <?php foreach($structure_users as $suser){?>
                        <li>
                            <label class="switch">
                                <input type="checkbox" name = "multiadd[]" value="<?=$suser['id']?>" />
                                <span class="switchslider round"></span>
                            </label>
                            <span><?=$suser['prenom']?> <?=$suser['nom']?></span>
                        </li>
                        <?php }?>
                    </ul>
                </div>
                <?php <span>Charge : <input type = "text" name = "charge" id = "charge" size = "4" maxlength = "4" pattern="[0-9]+"/>TRIM</span>
            */}?>
            </div>
            <div class = "row">
                <div class = "col-sm-5">
                    <span>
                        <input name="title" maxlength ="20" type = "text" class = "std-title" value = "" placeholder = "Titre">
                        <hr/>
                        <select name = "type_seance" class = "std-seance">
                        <?php foreach ($seances as $si){?>
                            <option value = "<?=$si['id']?>"> <?=$si['type']?></option>
                        <?php } ?>
                        </select> 
                        <br/>
                        <input type = "text" name = "hours" class= "std-hours" size = "3" maxlength = "3" pattern="[0-9]+"  required/>h <input type = "text" name = "min"  class = "std-min" size="3" maxlength = "2" pattern="[0-9]+" required/><br/>
                    </span>
                </div>
                <div class="col-sm-7">
                    <textarea name="txt" class = "std-txt" placeholder = "Description de la séance" spellcheck="false"></textarea>
                </div>
            </div>
            <div class="row justify-content-center">
                <input type="submit" name="save" class="button" id="submit" value = "OK">
                <?php /*<div id="delete"><input type = "submit" class="button"value ="&times"><input type="submit" name="delete" id = "warning" value ="Supprimer ?"></div>
                */ ?>
            </div>

        </form>

            
    </div>
</div>

<script type="text/javascript" src = "modal/planning_modal.js"></script>
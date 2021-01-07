<!--Modal-->
<div id="addModal" class="modal" onclick="dismissModal(event)" >
    <!-- Modal content -->
    <div class="modal-content animatetop">
        <span class="close" onclick="closeModal('addModal')">&times;</span>
        <h2 class="aln-center">Ajouter un entrainement</h2>
        <form id="addform" class="container" autocomplete="off" method="post">
            <input name="source" type="hidden" id="formname" value="add-form">

            <div class = "row">
                <div class="col-sm-8">
                    <input type = "text" name = "title" class = "std-title" placeholder="Titre" maxlength = "30">
                    <hr/>
                    <select name = "type_seance" class = "std-seance">

                    <?php foreach ($seances as $si){?>
                        <option value = "<?=$si['id']?>"> <?=$si['type']?></option>
                    <?php } ?>

                    </select> 
                    <br/>
                    <input type = "date" name = "date" id = "date" value = "<?=isset($date)?$date:''?>" required/>
                </div>
                        
                <div class="col-sm-4">
                    <input type = "text" name = "hours" class = "std-hours" maxlength = "3" pattern="[0-9]+" required>h <input type = "text" name = "min"  class = "std-min" maxlength = "2" pattern="[0-9]+" required/>
                    <br/>
                    <input name="distance" class = "std-distance" type = "text" maxlength = "5" pattern="[0-9.]+"/> km
                    <br/>
                    <input name="uphill" class = "std-uphill" type = "text" maxlength = "5" pattern="[0-9]+"/> m+
                </div>
                    
                    
                <div class="col-12">
                    <textarea placeholder="Commentaire" name="txt" class = "std-txt"></textarea>
                </div>
            
                <div class = "collapsible col-12"><span>Charge d'entrainement</span><span class = "sign">+</span></div>
                <div class = "collcontent col-12">
                    <p class="field">
                        <p>Difficulté ressentie de l'entraînement : <input type="text" name = "charge" id="trainingload" class = "sliderlabel" readonly></p>
                        <div class="slidecontainer">
                            <input type="range" min="1" max="10" value="5" class="slider" id="loadrange">
                        </div>
                    </p>
                    <h3>Intensités</h3>
                    <p class = "field">
                        <span>Très facile<br/>
                        <input type = "text" name = "hrzone1" class = "hrzone" pattern = "[0-9]+" maxlength="4" placeholder = "0"> min</span>
                        <span>Facile<br/>
                        <input type = "text" name = "hrzone2" class = "hrzone" pattern = "[0-9]+" maxlength="4" placeholder = "0"> min</span>
                        <span>Moyenne<br/>
                        <input type = "text" name = "hrzone3" class = "hrzone" pattern = "[0-9]+" maxlength="4" placeholder = "0"> min</span>
                        <span>Haute<br/>
                        <input type = "text" name = "hrzone4" class = "hrzone" pattern = "[0-9]+" maxlength="4" placeholder = "0"> min</span>
                        <span>Maximale<br/>
                        <input type = "text" name = "hrzone5" class = "hrzone" pattern = "[0-9]+" maxlength="4" placeholder = "0"> min</span>
                    </p>

                </div>
            </div>
            <p class="actions">
                <input type="submit" name="save" id="button" value = "Valider">
        
            </p>
        </form>

            
    </div>
</div>

<script type="text/javascript" src = "modal/modal.js"></script>
<script type="text/javascript" src = "modal/training_modal.js"></script>
<section id="history">

    <h2>Pogled nazaj</h2>

    <h3>Prikaz zadnjih 30 dni</h3>

    <div id="history-display"> 
        
        <?php foreach($history as $day) { ?>
            <?php if ($day === true) { ?>
                <div class="day true-day"></div>
            <?php } else { ?>
                <div class="day false-day"></div>
            <?php } ?>
        <?php }; ?>

    </div>

</section>
<section id="solutions">

    <h2>Naj Ljubljana zadiha</h2>

    <h3>Glasuj za tebi najljubše ukrepe!</h3>

    <div id="policy-ideas">

        <!-- ZAČETEK FOR LOOPA -->
        <?php foreach($policies as $policy) { ?>
        <div class="outer-box">
            <div class="title">
                <?php echo $policy["title"]; ?>;
            </div>
            <div class="icon">
                <?php echo $policy["icon"]; ?>;
            </div>
            <div class="description">
                <?php echo $policy["description"]; ?>
            </div>
            <div class="voting">
                <button type="button">GOR</button>
                <button type="button">DOL</button>
            </div>
        </div>
        <?php }; ?>
        <!-- KONEC FOR LOOPA -->

    </div> 

</section>

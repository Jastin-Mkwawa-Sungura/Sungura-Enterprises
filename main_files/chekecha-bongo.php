<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SUNGURA ENTERPRISES</title>
    <link rel="stylesheet" href="styles.css">
    <style></style>
  </head>
  <body>
    <header>
      <div class="logo">SUNGURA 🐇 <br> ENTERPRISES</div>

      <button class="toggle-Menu" id="menuToggle" aria-label="Toggle navigation">
        <span></span><span></span><span></span>
      </button>
      <nav>
        <ul id="navMenu">
          <li><a href="home.html">Home</a></li>
          <li><a href="businesses.html">Businesses</a></li>
          <li><a href="tech-solution.html">Tech-Solution</a></li>
          <li><a href="sports-&-games.html">Sports & Games</a></li>
        </ul>
      </nav>

      <a href="#" style="font-weight: bold; color: aliceblue;">Admistration</a> <!--administration/admistration.html-->    

      <button id="signoutBtn" class="signout">Sign Out</button>
      <div id="signoutModal" class="signout-modal" aria-hidden="true" role="dialog" aria-labelledby="signoutTitle">
        <div class="signout-modal-content">
          <h2 id="signoutTitle">Are you sure you want to Sign Out?</h2>
          <div>
            <button id="signoutYesBtn" class="btn btn-primary">Yes</button>
            <button id="cancelBtn" class="btn btn-danger">Cancel</button>
          </div>
        </div>
      </div>
      
      <!-- Texts will display by bringing-->
      <marquee class="marquee" direction="left" behavior="scroll" scrollamount="5">
        <div class="blinking-text">🚀 We give you important Services, Just enjoy your day!🌟</div>
      </marquee>
    </header>

    <div class="main-container">
      <main class="body-container">
        <!-- Message Form -->
        <section class="column1">
          <div class="question">Soma swali hapo chini kisha toa jibu!</div>
          <form action="chekecha-bongo-submit.php" method="POST">
            <input type="text" class="sender-name" name="sender" placeholder="Your Name" aria-label="Your Name" required>
            <textarea class="message-input" name="message" rows="4" placeholder="Type a message here..." aria-label="Message" required></textarea>
            <button type="submit" class="send-btn">Send</button>
            <button type="reset" class="clear-btn">Clear</button>
          </form>
        </section>
        <section class="column2">
            <?php
              include 'config.php';
              try {
                // Fetch data (example: users table)
                $stmt = $pdo->query("SELECT * FROM chekecha_bongo ORDER BY submitted_at DESC");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<b>" . htmlspecialchars($row['submitted_at']) . " > " . "</b>";
                    echo "<b>" . "<i>" . htmlspecialchars($row['sender']) . "</i>" . "</b>" . "<br>";
                    echo htmlspecialchars($row['message']);
                    echo "<hr>";
                }
              } catch (PDOException $e) {
                echo "Connection failed: " . $e->getMessage();
              }
            ?>
        </section>
      </main>
      <div class="question-container">
        <h3>Swali la leo!</h3>
        <div class="main-question">
        <hr style="border: 1px solid #196ad4; margin-top: -10px;">          
          <i>Swali hili hapa</i>
        <hr style="border: 1px solid #196ad4;">              
        </div>  
      </div>
      <div class="instructions-container">
        <h2>🧠🎉 CHEKECHA BONGO 🎉🧠</h2>
        <div class="main-instructions">
          <section class="instructions">
            Unapenda michezo ya kufikirisha? <br> Karibu kwenye CHEKECHA BONGO, mchezo wa kipekee wa maarifa na ushindani wa haraka!
            <i><b>Soma swali kwa umakini kisha rudi kwenye ukurasa wa kujibia swali utoe jibu lako.</b></i>
                  
            <h4>Jinsi ya Kushiriki</h4>
            <ol style="line-height: 0.9;">
              <li>Swali litaulizwa kwa washiriki wote.</li> <br>
              <li>Ili kushiriki, <b>changia Tsh 500/= tu</b> kama kiingilio cha droo ya mchezo.</li> <br>
              <li>Toa jibu lako kwa <b>usahihi</b> na kwa <b>haraka</b> zaidi.</li>
            </ol>
            
            <h4>Kanuni za Ushindi</h4>
            Chekecha Bongo ni mchezo wa kutumia akili na wepesi ambapo, linaulizwa swali na washiriki watatakiwa kujibu swali hilo. <br>
            👉Mshiriki atakayejibu swali kwa usahihi na kwa wakati, atakuwa ameshinda na atapewa zawadi ya ushindi kiasi cha fedha cha <b> shilingi elfu kumi (TSh 10,000/=).</b><br>
            👉Endapo washiriki zaidi ya mmoja watajibu swali kwa usahihi, mshiriki atakayekuwa ametoa jibu mapema zaidi kuliko wengine ndiye atakayekuwa mshindi na atapewa zawadi ya ushindi. <br>
            👉Hivyo wepesi wako wa kutafuta jibu na kulitoa kwa wakati, itakusaidia kuwa mshindi. <br>
            👉Muda wa kujibu swali ni dakika 30 kuanzia pale swali linapoulizwa. <p>
            
            <h4><i>Shindano hili ni nafasi yako ya kuonyesha uwezo wako wa kufikiria haraka na kushinda zawadi papo hapo. Jiandae kushinda. Usikose!</i></h4><p>

            <h3>CHEKECHA BONGO – Fikiri, Jibu, Shinda! 🎯</h3>
          </section>
          <section class="tangazo1"><img src="items/tangazo1.png" alt=""></section>
        </div>
      </div>
    </div>    

    <footer>
      <p><i>&copy; 2025 Sungura Enterprises. | jastinsungura@gmail.com | +255 688 979 492</i></p>
    </footer>
    <script src="scripts.js">

    </script>
  </body>
</html>

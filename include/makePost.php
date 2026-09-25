<?php
require_once __DIR__ . '/session_boot.php';

if (!isset($_SESSION['user'])) {
    echo '<p class="error">You must be logged in to post.</p>';
    return; 
}

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/upload_helper.php';
$conn = db_connect();

$username = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id, profile_pic FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

$user = $result->fetch_assoc();

if (!$user) {
    echo '<p class="error">User not found in database.</p>';
    return;
}

//stocke l'ID utilisateur et la photo de profil pour usage ultérieur
$user_id = $user['id'];
$profile_pic = $user['profile_pic'];

?>

<?php
//si le formulaire a été soumis en méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'] ?? '';
    $content = $_POST['content'] ?? '';
    $image_path = null;

    //vérifie si les champs obligatoires sont remplis
    if (!$category || !$content) {
        echo '<p class="error">Please fill out all required fields.</p>';
    } else {
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            [$valid, $error] = validate_uploaded_image($_FILES['image']);
            if (!$valid) {
                echo '<p class="error">' . $error . '</p>';
                return;
            }

            $upload_dir = __DIR__ . '/../uploads/' . date('Y/m') . '/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $filename = time() . '_' . basename($_FILES['image']['name']);
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = 'uploads/' . date('Y/m') . '/' . $filename;
            } else {
                echo '<p class="error">Image upload failed.</p>';
                return;
            }
        }

        //prépare la requête pour insérer le post dans la base de données
        $stmt = $conn->prepare("INSERT INTO posts (user_id, category, content, image) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $category, $content, $image_path);

            if ($stmt->execute()) {
                header("Location: ../index.php");       //redirige vers l'accueil
                exit();
            } else {
                echo '<p class="error">Something went wrong. Please try again.</p>';
            }
        } else {
            echo '<p class="error">DB error preparing statement.</p>';
        }
    }
}
?>

<section>
    <!-- Formulaire de création de post -->
    <form action="include/makePost.php" method="post" enctype="multipart/form-data">
        <div class="create-post">
            <div class="create-post-input">
                <!-- Affiche la photo de profil de l'utilisateur -->
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" class="profile-pic">
                <!-- Zone de texte pour entrer le contenu du post -->
                <textarea name="content" id="content" rows="2" placeholder="Post something.." required></textarea>
            </div>

            <div class="create-post-links">
                <!-- Champ caché pour upload d'image + icône cliquable -->
                <input type="file" name="image" id="image-upload" accept="image/*" style="display: none;" />
                <label for="image-upload" class="camera-label">
                    <svg class="icon icon-camera" viewBox="0 0 24 24" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2Z"/><circle cx="12" cy="13" r="4"/></svg>
                </label>
                <!-- Sélecteur de catégorie -->
                <div class="category">
                    <select name="category" id="category" required>
                        <option value="">-- Select Category --</option>
                        <option value="music">Music</option>
                        <option value="art">Art</option>
                        <option value="quotes">Quotes</option>
                        <option value="games">Games</option>
                        <option value="anime">Anime</option>
                        <option value="coding">Coding</option>
                    </select>
                </div>
                <!-- Bouton pour publier le post -->
                <button type="submit" class="btn btn-primary">Post</button>
            </div>
        </div>
    </form>
</section>

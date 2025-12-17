<?php
/**
 * Stats Card Component
 * 
 * Usage:
 * renderStatsCard([
 *     'title' => 'Card Title',
 *     'number' => '5',
 *     'icon' => 'fa-tasks',
 *     'color' => 'blue', // blue, green, orange, red, purple, teal
 *     'footer' => 'Additional info',
 *     'style' => 'colored' // colored or white
 * ]);
 */

function renderStatsCard($config) {
    $defaults = [
        'title' => 'Card Title',
        'number' => '0',
        'icon' => 'fa-chart-line',
        'color' => 'blue',
        'footer' => '',
        'style' => 'colored', // colored or white
        'link' => '#'
    ];
    
    $card = array_merge($defaults, $config);
    
    // Determine card class
    if ($card['style'] === 'white') {
        $cardClass = 'stats-card card-white border-' . $card['color'];
    } else {
        $cardClass = 'stats-card card-' . $card['color'];
    }
    
    ob_start();
    ?>
    <div class="<?php echo $cardClass; ?>" onclick="window.location.href='<?php echo $card['link']; ?>'">
        <div class="stats-card-header">
            <h3 class="stats-card-title"><?php echo $card['title']; ?></h3>
            <div class="stats-card-icon">
                <i class="fas <?php echo $card['icon']; ?>"></i>
            </div>
        </div>
        
        <div class="stats-card-body">
            <div class="stats-card-number"><?php echo $card['number']; ?></div>
        </div>
        
        <?php if (!empty($card['footer'])): ?>
        <div class="stats-card-footer">
            <?php echo $card['footer']; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render multiple stats cards in a grid
 */
function renderStatsGrid($cards, $columns = 4) {
    echo '<div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">';
    foreach ($cards as $card) {
        echo renderStatsCard($card);
    }
    echo '</div>';
}
?>
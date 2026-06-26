// Load More Hotels Functionality
document.addEventListener('DOMContentLoaded', function() {
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const hotelCards = document.querySelectorAll('.hotel-card');
    
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            // Get all hidden hotel cards
            const hiddenCards = document.querySelectorAll('.hotel-card.hidden');
            
            if (hiddenCards.length > 0) {
                // Show all hidden cards
                hiddenCards.forEach(card => {
                    card.classList.remove('hidden');
                });
                
                // Hide the button after showing all cards
                loadMoreBtn.classList.add('hidden');
                
                // Smooth scroll to first newly revealed card
                setTimeout(() => {
                    hiddenCards[0].scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'nearest' 
                    });
                }, 100);
            }
        });
    }

    
});

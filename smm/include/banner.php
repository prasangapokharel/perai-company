<!-- Modern Responsive Banner Component -->
<div class="relative w-full overflow-hidden">
    <!-- Banner Image (responsive at different breakpoints) -->
    <div class="relative h-56 md:h-80 lg:h-96 w-full">
        <!-- Background Image - add your image URL in the src attribute -->
        <img 
            src="<?php echo !empty($banner_image) ? htmlspecialchars($banner_image) : '../assets/images/default-banner.jpg'; ?>" 
            alt="<?php echo !empty($banner_title) ? htmlspecialchars($banner_title) : 'Banner Title'; ?>"
            class="absolute inset-0 w-full h-full object-cover"
        />
        

        <!-- Gradient Overlay for better text readability -->
        <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/50 to-black/70"></div>
        
        <!-- Content Container -->
        <div class="absolute inset-0 flex flex-col items-center justify-center px-4 md:px-8 lg:px-16">
            <!-- Optional Subtitle -->
            <?php if(!empty($banner_subtitle)): ?>
            <span class="inline-block px-4 py-1 mb-4 text-sm font-medium tracking-wider uppercase bg-primary-600 text-white rounded-full">
                <?php echo htmlspecialchars($banner_subtitle); ?>
            </span>
            <?php endif; ?>
            
            <!-- Main Title -->
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white text-center max-w-4xl mb-4 leading-tight">
                <?php echo !empty($banner_title) ? htmlspecialchars($banner_title) : 'Welcome to Our Website'; ?>
            </h1>
            
            <!-- Optional Description -->
            <?php if(!empty($banner_description)): ?>
            <p class="text-base md:text-lg text-gray-200 text-center max-w-2xl mb-6">
                <?php echo htmlspecialchars($banner_description); ?>
            </p>
            <?php endif; ?>
            
            <!-- Optional CTA Buttons -->
            <?php if(!empty($show_cta_buttons)): ?>
            <div class="flex flex-wrap justify-center gap-3 mt-2">
                <a href="<?php echo !empty($primary_button_url) ? htmlspecialchars($primary_button_url) : '#'; ?>" 
                   class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl 
                          transition-colors duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <?php echo !empty($primary_button_text) ? htmlspecialchars($primary_button_text) : 'Get Started'; ?>
                </a>
                
              
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Optional Decorative Elements -->
    <div class="absolute -bottom-1 left-0 right-0">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 60" class="w-full h-auto text-white fill-current">
            <path d="M0,0V60H1440V0C1139.13,48,720.72,60,360.36,60C240.24,60,120.12,40,0,0Z"></path>
        </svg>
    </div>
</div>

<!-- 
USAGE INSTRUCTIONS:

1. Include this banner component in your PHP file
2. Set the following variables before including this component:

   $banner_image = 'path/to/your/image.jpg';    // URL of the banner image
   $banner_title = 'Your Banner Title';         // Main heading text
   $banner_subtitle = 'Optional Subtitle';      // Optional subtitle (remove if not needed)
   $banner_description = 'Optional description text goes here'; // Optional description (remove if not needed)
   
   // For CTA buttons (optional)
   $show_cta_buttons = true;                    // Set to false to hide buttons
   $primary_button_text = 'Get Started';        // Primary button text
   $primary_button_url = '/get-started';        // Primary button URL
   $secondary_button_text = 'Learn More';       // Secondary button text
   $secondary_button_url = '/learn-more';       // Secondary button URL

3. Customize colors by changing the Tailwind classes (bg-primary-600, etc.)
-->

<!-- Example Usage -->
<!--
<?php
// Define banner variables
$banner_image = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQTOlf3PvF1RWZ2ZB5r6sZdDMAvBn60hWjapg&s';
$banner_title = 'Welcome to Our Amazing Platform';
$banner_subtitle = 'New Features';
$banner_description = 'Discover all the amazing things you can do with our platform. Start your journey today!';
$show_cta_buttons = true;
$primary_button_text = 'Get Started';
$primary_button_url = 'https://hostinger.com?REFERRALCODE=SIFPRASANXXU';

?>
-->

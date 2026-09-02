<?php

namespace App\View\Composers;

use Illuminate\View\View;

class ThemeComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $userTheme = getUserRoleTheme();
        $brandColors = getBrandColors();
        
        $view->with([
            'userTheme' => $userTheme,
            'brandColors' => $brandColors
        ]);
    }
}

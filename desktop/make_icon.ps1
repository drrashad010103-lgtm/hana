Add-Type -AssemblyName System.Drawing
$bmp = New-Object System.Drawing.Bitmap 256,256
$g = [System.Drawing.Graphics]::FromImage($bmp)
$g.SmoothingMode = 'AntiAlias'
$g.TextRenderingHint = 'AntiAlias'
$rect = New-Object System.Drawing.Rectangle 0,0,256,256
$c1 = [System.Drawing.Color]::FromArgb(255,91,33,182)
$c2 = [System.Drawing.Color]::FromArgb(255,13,148,136)
$angle = [System.Single]45
$brush = New-Object System.Drawing.Drawing2D.LinearGradientBrush -ArgumentList $rect, $c1, $c2, $angle
$g.FillEllipse($brush, 4, 4, 248, 248)
$font = New-Object System.Drawing.Font -ArgumentList 'Segoe UI', ([single]96), ([System.Drawing.FontStyle]::Bold), ([System.Drawing.GraphicsUnit]::Pixel)
$fmt = New-Object System.Drawing.StringFormat
$fmt.Alignment = 'Center'
$fmt.LineAlignment = 'Center'
$box = New-Object System.Drawing.RectangleF 0,0,256,256
$g.DrawString('DB', $font, [System.Drawing.Brushes]::White, $box, $fmt)
$g.Dispose()
$icon = [System.Drawing.Icon]::FromHandle($bmp.GetHicon())
$fs = [IO.File]::Create('D:\RS_Own\desktop\app.ico')
$icon.Save($fs)
$fs.Close()
$icon.Dispose(); $bmp.Dispose()
$ok = (Get-Item 'D:\RS_Own\desktop\app.ico').Length
"icon bytes: $ok"

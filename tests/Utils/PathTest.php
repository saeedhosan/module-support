<?php

declare(strict_types=1);

use SaeedHosan\Module\Support\Utils\Path;

test('path join', function () {

    expect(Path::join('/var', 'log//', 'app', 'laravel.log'))->toBe('/var/log/app/laravel.log');

    expect(Path::join('C:\\path', 'to\\', 'dir', 'file.txt'))->toBe('C:/path/to/dir/file.txt');

    expect(Path::join('/a/', '/b/', '/c'))->toBe('/a/b/c');
});

test('path normalize', function () {

    expect(Path::normalize('\\a\\\\b////c'))->toBe('/a/b/c');

    expect(Path::normalize('C:\\a\\b'))->toBe('C:/a/b');

    expect(Path::normalize('/a//b'))->toBe('/a/b');
});

test('path real returns null for non-existent path', function () {
    expect(Path::real('/this/definitely/does/not/exist-'.bin2hex(random_bytes(4))))->toBeNull();
});

test('path replace and replace first', function () {
    $path = '/storage/app/users/photos/avatar.png';

    expect(Path::replaceFirst('/storage/app', '/public', $path))
        ->toBe('/public/users/photos/avatar.png');

    expect(Path::replace('photos', 'public', $path))
        ->toBe('/storage/app/users/public/avatar.png');
});

test('path dirname basename filename and extension', function () {

    $path = '/var/www/html/image.backup.tar.gz';

    expect(Path::dirname($path))->toBe('/var/www/html');

    expect(Path::basename($path))->toBe('image.backup.tar.gz');

    expect(Path::filename($path))->toBe('image.backup.tar');

    expect(Path::extension($path))->toBe('gz');
});

test('path is absolute', function () {
    expect(Path::isAbsolute('/'))->toBeTrue();
    expect(Path::isAbsolute('/var/log'))->toBeTrue();
    expect(Path::isAbsolute('D:/data'))->toBeTrue();

    expect(Path::isAbsolute('relative/path'))->toBeFalse();
    expect(Path::isAbsolute('..\\up\\one'))->toBeFalse();
    expect(Path::isAbsolute('./here'))->toBeFalse();
    expect(Path::isAbsolute(''))->toBeFalse();
});

test('path current', function () {

    // __DIR__ is the directory of this test file
    $expectedBase = Path::normalize(__DIR__);

    // No extra segments
    $current = Path::normalize(Path::current());

    expect($current)->toBe($expectedBase);

    // With extra segments
    $withSegments = Path::normalize(
        Path::current('fixtures', 'file.txt')
    );

    expect($withSegments)->toBe(
        Path::join($expectedBase, 'fixtures', 'file.txt')
    );
});

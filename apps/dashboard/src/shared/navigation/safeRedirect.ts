export const safeRedirect = (value: unknown): string => {
    if (typeof value !== 'string' || !value.startsWith('/') || value.startsWith('//')) {
        return '/app';
    }

    let decoded: string;

    try {
        decoded = decodeURIComponent(value);
    } catch {
        return '/app';
    }

    const reservedPrefixes = ['/dashboard', '/api', '/sanctum'];

    if (
        decoded.startsWith('//') ||
        reservedPrefixes.some((prefix) => decoded === prefix || decoded.startsWith(`${prefix}/`))
    ) {
        return '/app';
    }

    return decoded;
};

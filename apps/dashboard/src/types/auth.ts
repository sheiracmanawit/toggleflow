export interface Owner {
    id: number;
    name: string;
    email: string;
}

export interface Credentials {
    email: string;
    password: string;
}

export interface DataResponse<T> {
    data: T;
}

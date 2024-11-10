package cz.lriedel.photo.uploader.processor;

public interface Processor<JOB_ARGS, RETURN_VALUE> {

    RETURN_VALUE process(JOB_ARGS args) throws Exception;
}

package cz.lriedel.photo.uploader.processor;

public interface Processor<JOB_ARGS> {

    void process(JOB_ARGS args) throws Exception;
}
